<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Krediidi-broneerimise reegel:
 * krediiti peab saama kasutada formaadi entry-minperiod järgi,
 * mitte fikseeritud 24h (entry-mincancel) järgi.
 *
 * Seltskonnatrenn: minperiod=60, mincancel=1440.
 * +2h kuupäev => available=true, pastentry=true => peab lubama (nii krediit kui raha).
 * +30min kuupäev => available=false => peab blokeerima (mõlemad).
 */
class CreditBookingTest extends TestCase
{
    use RefreshDatabase;

    protected int $typeId;
    protected int $levelId;
    protected int $lengthId;
    protected int $locationId;
    protected int $entryId;
    protected int $coachId;

    protected function setUp(): void
    {
        parent::setUp();

        $now = Carbon::now();

        foreach ([1 => 'Admin', 3 => 'Coach', 4 => 'Client'] as $id => $title) {
            DB::table('racster_assets')->insert([
                'id' => $id, 'uid' => 1, 'type' => 'userrole', 'title' => $title,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $this->typeId = DB::table('racster_assets')->insertGetId([
            'uid' => 1, 'type' => 'entry-type', 'title' => 'Seltskonnatrenn',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('racster_assets')->insert([
            ['uid' => 1, 'type' => 'entry-minperiod', 'parent' => $this->typeId, 'title' => '60', 'created_at' => $now, 'updated_at' => $now],
            ['uid' => 1, 'type' => 'entry-mincancel', 'parent' => $this->typeId, 'title' => '1440', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->levelId = DB::table('racster_assets')->insertGetId([
            'uid' => 1, 'type' => 'client-level', 'title' => 'Test level',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->lengthId = DB::table('racster_assets')->insertGetId([
            'uid' => 1, 'type' => 'entry-length', 'parent' => $this->typeId, 'title' => '60',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->locationId = DB::table('racster_assets')->insertGetId([
            'uid' => 1, 'type' => 'entry-location', 'title' => 'Test hall',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $coach = User::factory()->create([
            'user_roles' => '3',
            'first_name' => 'Coach',
            'last_name' => 'Test',
        ]);
        $this->coachId = $coach->id;

        $this->entryId = DB::table('racster_entries')->insertGetId([
            'creator_id' => $this->coachId,
            'entry_title' => 'Seltskonnatrenn test',
            'entry_type' => $this->typeId,
            'client_level' => $this->levelId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected function makeClientWithCredit(int $credit = 100): User
    {
        $user = User::factory()->create([
            'user_roles' => '4',
            'first_name' => 'Clara',
            'last_name' => 'Client',
            'user_level' => $this->levelId,
        ]);
        DB::table('racster_user_transactions')->insert([
            'creator_id' => $user->id,
            'transaction_type' => 'added',
            'transaction_amount' => $credit,
            'transaction_comment' => 'test credit',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return $user;
    }

    protected function makeDate(Carbon $start, int $limit = 10, int $count = 0, int $price = 25): int
    {
        $now = Carbon::now();

        return (int) DB::table('racster_entry_dates')->insertGetId([
            'creator_id' => $this->coachId,
            'entry_id' => $this->entryId,
            'entry_start' => $start->toDateTimeString(),
            'entry_ending' => $start->copy()->addHour()->toDateTimeString(),
            'entry_length' => $this->lengthId,
            'entry_location' => $this->locationId,
            'client_limit' => $limit,
            'client_count' => $count,
            'entry_price' => $price,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function test_credit_booking_allowed_inside_cancel_window_but_outside_booking_cutoff(): void
    {
        // +2h: tühistamisaken (24h) on möödas, broneerimisaken (60min) on avatud.
        $client = $this->makeClientWithCredit(100);
        $dateId = $this->makeDate(Carbon::now()->addHours(2));

        // Info: available=true (formaadi järgi), pastentry=true (tühistamine suletud), okcredit=true
        $info = $this->actingAs($client)->post('/et/acquireEntryData', ['eid' => $dateId]);
        $info->assertOk()->assertJsonPath('success', true);
        $entry = $info->json('entry');
        $this->assertTrue($entry['available'], 'formaadi broneerimisaken (60min) peab olema avatud');
        $this->assertTrue($entry['pastentry'], 'tühistamisaken (1440min) peab olema suletud');
        $this->assertTrue($entry['okcredit'], 'krediidijääk peab katma hinna');

        // Broneerimine krediidiga peab õnnestuma (vana koodi puhul UI sundis raha, backendil puudus kontroll).
        $attend = $this->actingAs($client)->post('/et/attendPeriod', ['eid' => $dateId]);
        $attend->assertOk()->assertJsonPath('success', true);
        $this->assertArrayNotHasKey('pay_url', $attend->json(), 'krediidi puhul ei tohi Stripe-i suunata');

        $this->assertDatabaseHas('racster_entry_users', [
            'date_id' => $dateId, 'user_id' => $client->id, 'user_type' => 'client',
        ]);
        $this->assertDatabaseHas('racster_user_transactions', [
            'date_id' => $dateId, 'user_id' => $client->id, 'transaction_type' => 'used',
        ]);
    }

    public function test_booking_blocked_inside_format_cutoff(): void
    {
        // +30min: seespool 60min broneerimisakent => nii krediit kui raha blokeeritud.
        $client = $this->makeClientWithCredit(100);
        $dateId = $this->makeDate(Carbon::now()->addMinutes(30));

        $info = $this->actingAs($client)->post('/et/acquireEntryData', ['eid' => $dateId]);
        $info->assertOk();
        $this->assertFalse($info->json('entry.available'));

        $attend = $this->actingAs($client)->post('/et/attendPeriod', ['eid' => $dateId]);
        $attend->assertOk()->assertJsonPath('success', false);
    }

    public function test_credit_booking_allowed_far_future(): void
    {
        // +30h: mõlemad aknad avatud.
        $client = $this->makeClientWithCredit(100);
        $dateId = $this->makeDate(Carbon::now()->addHours(30));

        $info = $this->actingAs($client)->post('/et/acquireEntryData', ['eid' => $dateId]);
        $this->assertTrue($info->json('entry.available'));
        $this->assertFalse($info->json('entry.pastentry'));

        $attend = $this->actingAs($client)->post('/et/attendPeriod', ['eid' => $dateId]);
        $attend->assertOk()->assertJsonPath('success', true);
    }
}
