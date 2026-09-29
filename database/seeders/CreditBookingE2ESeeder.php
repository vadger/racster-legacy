<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * E2E setup for credit-booking rule:
 * "Krediiti peab saama kasutada formaadi reeglite järgi, mitte fikseeritud 24h järgi."
 *
 * Live näide (racster-live-data/racster-assets-table-data.csv):
 *   Seltskonnatrenn (live id 9): entry-minperiod=60, entry-mincancel=1440
 *   => 2h enne trenni: broneerimine lubatud (available=true),
 *      aga tühistamine juba suletud (pastentry=true).
 *   Vana UI sundis siis "pay-for-onetime" (ainult raha),
 *   uus UI/backend lubab krediiti formaadi reegli (60min) järgi.
 *
 * See seeder:
 *  1. Sünkroniseerib live-CSV-st minperiod/mincancel väärtused lokaalse
 *     Seltskonnatrenn-tüübi külge (pealkirja järgi, mitte id järgi,
 *     sest lokaalsed id-d erinevad live omadest).
 *  2. Loob deterministliku testistsenaariumi ajaliselt suhteliste kuupäevadega,
 *     et Playwright saaks kontrollida kõiki harusid.
 *
 * Idempotentne: võib käivitada korduvalt. Tuvastab E2E kirjed markeri järgi.
 *
 * Kasutus:
 *   php artisan db:seed --class=CreditBookingE2ESeeder
 */
class CreditBookingE2ESeeder extends Seeder
{
    public const CLIENT_EMAIL = 'e2e-client@racster.com';
    public const POOR_EMAIL = 'e2e-poor@racster.com';
    public const COACH_EMAIL = 'e2e-coach@racster.com';
    public const PASSWORD = 'secret';
    public const ENTRY_TITLE = 'E2E Seltskonnatrenn - krediidi test';

    public function run(): void
    {
        $now = Carbon::now();

        $this->syncLiveFormatRules();
        $this->ensureBaseAssets($now);

        $typeId = $this->resolveSocialTrainingTypeId();
        $levelId = DB::table('racster_assets')->where('type', 'client-level')->whereNull('deleted_at')->value('id');
        $lengthId = DB::table('racster_assets')->where('type', 'entry-length')->whereNull('deleted_at')->value('id');
        $locationId = DB::table('racster_assets')->where('type', 'entry-location')->whereNull('deleted_at')->value('id');

        $coachId = $this->ensureUser($now, self::COACH_EMAIL, 'E2E', 'Coach', '3', null);
        $clientId = $this->ensureUser($now, self::CLIENT_EMAIL, 'E2E', 'Client', '4', $levelId);
        $poorId = $this->ensureUser($now, self::POOR_EMAIL, 'E2E', 'Poor', '4', $levelId);

        // Kliendile krediiti (jääk 100, hinda 25 katab 4x). Idempotentne: täiendame puudujäägi.
        $this->ensureCredit($clientId, 100);

        // Testitrenni entry (üks, 4 kuupäeva all)
        $entryId = DB::table('racster_entries')
            ->where('entry_title', self::ENTRY_TITLE)
            ->whereNull('deleted_at')
            ->value('id');

        if (! $entryId) {
            $entryId = DB::table('racster_entries')->insertGetId([
                'creator_id' => $coachId,
                'entry_title' => self::ENTRY_TITLE,
                'entry_description' => 'E2E: Seltskonnatrenn 60min/1440h reegliga. ÄRA KUSTUTA.',
                'entry_type' => $typeId,
                'client_level' => $levelId,
                'various_clients' => 0,
                'recurring_entry' => 0,
                'entry_monthly_fee' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('racster_entries')->where('id', $entryId)->update([
                'entry_type' => $typeId,
                'client_level' => $levelId,
                'updated_at' => $now,
            ]);
        }

        // Treener entry külge (date_id null = põhitreener)
        DB::table('racster_entry_users')
            ->where('entry_id', $entryId)
            ->where('user_type', 'coach')
            ->whereNull('date_id')
            ->whereNull('deleted_at')
            ->delete();
        DB::table('racster_entry_users')->insert([
            'creator_id' => $coachId,
            'entry_id' => $entryId,
            'date_id' => null,
            'user_type' => 'coach',
            'user_id' => $coachId,
            'user_quantity' => 1,
            'paying' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 4 kuupäeva: A=+2h (põhitest), B=+30min (suletud), C=+30h (avatud), D=+2h täis.
        // E2E entry on testide päralt: kustutame kõik selle varasemad kuupäevad
        // (koos osalejate ja tehingutega), et kordusjooksud ei jätaks vanu ridasid.
        $oldDateIds = DB::table('racster_entry_dates')
            ->where('entry_id', $entryId)
            ->pluck('id')
            ->all();
        if (! empty($oldDateIds)) {
            DB::table('racster_entry_users')->whereIn('date_id', $oldDateIds)->delete();
            DB::table('racster_user_transactions')->whereIn('date_id', $oldDateIds)->delete();
            DB::table('racster_entry_dates')->whereIn('id', $oldDateIds)->delete();
        }

        $dates = [
            'A' => ['offset' => '+2 hours', 'limit' => 10, 'count' => 0, 'price' => 25, 'expect' => 'credit lubatud (available=true, pastentry=true)'],
            'B' => ['offset' => '+30 minutes', 'limit' => 10, 'count' => 0, 'price' => 25, 'expect' => 'suletud (available=false)'],
            'C' => ['offset' => '+30 hours', 'limit' => 10, 'count' => 0, 'price' => 25, 'expect' => 'credit lubatud (available=true, pastentry=false)'],
            'D' => ['offset' => '+2 hours', 'limit' => 2, 'count' => 2, 'price' => 25, 'expect' => 'täis (limit ületatud)'],
        ];

        $result = [];
        foreach ($dates as $key => $cfg) {
            $start = (clone $now)->modify($cfg['offset']);
            $end = (clone $start)->modify('+1 hour');

            $dateId = DB::table('racster_entry_dates')->insertGetId([
                'creator_id' => $coachId,
                'entry_id' => $entryId,
                'entry_start' => $start->toDateTimeString(),
                'entry_ending' => $end->toDateTimeString(),
                'entry_length' => $lengthId,
                'entry_location' => $locationId,
                'private_entry' => 0,
                'client_limit' => $cfg['limit'],
                'client_count' => $cfg['count'],
                'entry_quantity' => 1,
                'entry_price' => $cfg['price'],
                'extra_price' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // D: simuleeri täitumist kahe võõra kliendiga (et limit-used<1 haru testida)
            if ($key === 'D') {
                $this->fillDateWithDummies($entryId, $dateId, $now, $cfg['count']);
            }

            $result[$key] = ['id' => $dateId, 'start' => $start->toDateTimeString()] + $cfg;
        }

        // Kirjuta Playwrighti spec-i jaoks kuupäevade JSON (seeder teab täpseid id-sid).
        $jsonPath = base_path('tests/e2e/.e2e-dates.json');
        @mkdir(dirname($jsonPath), 0777, true);
        file_put_contents($jsonPath, json_encode([
            'entryId' => $entryId,
            'A' => ['id' => $result['A']['id'], 'start' => $result['A']['start']],
            'B' => ['id' => $result['B']['id'], 'start' => $result['B']['start']],
            'C' => ['id' => $result['C']['id'], 'start' => $result['C']['start']],
            'D' => ['id' => $result['D']['id'], 'start' => $result['D']['start']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->command->info('E2E krediidi-testandmed valmis:');
        $this->command->info('  Seltskonnatrenn type_id='.$typeId.' (minperiod=60, mincancel=1440)');
        $this->command->info('  Entry: "'.self::ENTRY_TITLE.'" id='.$entryId);
        foreach ($result as $key => $r) {
            $this->command->info("  Kuupäev $key: date_id={$r['id']} start={$r['start']} limit={$r['limit']} count={$r['count']} -> {$r['expect']}");
        }
        $this->command->info('  Kuupäevade JSON: tests/e2e/.e2e-dates.json');
        $this->command->info('  Klient krediidiga: '.self::CLIENT_EMAIL.' / '.self::PASSWORD.' (jääk>=100)');
        $this->command->info('  Klient krediidita: '.self::POOR_EMAIL.' / '.self::PASSWORD.' (jääk=0)');
        $this->command->info('  Treener: '.self::COACH_EMAIL.' / '.self::PASSWORD);
    }

    /**
     * Loe live-CSV-st formaadireeglid ja kanna lokaalsele Seltskonnatrenn-tüübile.
     * Match pealkirja järgi (live id 9 <-> local id 5), sest id-d erinevad.
     */
    protected function syncLiveFormatRules(): void
    {
        $csv = base_path('racster-live-data/racster-assets-table-data.csv');
        if (! is_file($csv)) {
            return;
        }

        $rows = array_values(array_filter(array_map('str_getcsv', file($csv)), fn ($r) => count($r) > 1 && trim(implode('', $r)) !== ''));
        if (empty($rows)) {
            return;
        }
        $header = array_map('trim', $rows[0]);
        $data = [];
        foreach (array_slice($rows, 1) as $row) {
            if (count($row) !== count($header)) {
                continue;
            }
            $data[] = array_combine($header, $row);
        }

        $liveTypes = [];
        foreach ($data as $r) {
            if (($r['type'] ?? '') === 'entry-type' && empty($r['deleted_at'])) {
                $liveTypes[$r['id']] = $r['title'];
            }
        }

        // Otsi live Seltskonnatrenn
        $liveSocialId = array_search('Seltskonnatrenn', $liveTypes, true);
        if ($liveSocialId === false) {
            return;
        }

        $liveRules = [];
        foreach ($data as $r) {
            if (in_array($r['type'] ?? '', ['entry-minperiod', 'entry-mincancel'], true)
                && (string) ($r['parent'] ?? '') === (string) $liveSocialId
                && empty($r['deleted_at'])) {
                $liveRules[$r['type']] = (int) $r['title'];
            }
        }

        if (empty($liveRules)) {
            return;
        }

        $localTypeId = $this->resolveSocialTrainingTypeId();
        if (! $localTypeId) {
            return;
        }

        $now = Carbon::now();
        foreach ($liveRules as $type => $minutes) {
            $exists = DB::table('racster_assets')
                ->where('type', $type)
                ->where('parent', $localTypeId)
                ->whereNull('deleted_at')
                ->first();
            if ($exists) {
                DB::table('racster_assets')->where('id', $exists->id)->update([
                    'title' => (string) $minutes,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('racster_assets')->insert([
                    'uid' => 1,
                    'type' => $type,
                    'parent' => $localTypeId,
                    'title' => (string) $minutes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (isset($this->command)) {
            $this->command->info('Live-reeglid sünkroonitud (Seltskonnatrenn): '.json_encode($liveRules));
        }
    }

    protected function resolveSocialTrainingTypeId(): ?int
    {
        return DB::table('racster_assets')
            ->where('type', 'entry-type')
            ->where('title', 'Seltskonnatrenn')
            ->whereNull('deleted_at')
            ->value('id');
    }

    protected function ensureBaseAssets(Carbon $now): void
    {
        $needRoles = [1 => 'Admin', 3 => 'Coach', 4 => 'Client'];
        foreach ($needRoles as $id => $title) {
            DB::table('racster_assets')->updateOrInsert(['id' => $id], [
                'uid' => 1,
                'type' => 'userrole',
                'title' => $title,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        if (! $this->resolveSocialTrainingTypeId()) {
            $id = DB::table('racster_assets')->insertGetId([
                'uid' => 1, 'type' => 'entry-type', 'title' => 'Seltskonnatrenn',
                'descr' => 'kuni 20 osalejat', 'extra' => '20',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('racster_assets')->insert([
                ['uid' => 1, 'type' => 'entry-minperiod', 'parent' => $id, 'title' => '60', 'created_at' => $now, 'updated_at' => $now],
                ['uid' => 1, 'type' => 'entry-mincancel', 'parent' => $id, 'title' => '1440', 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        foreach (['client-level' => 'Kõik tasemed', 'entry-length' => '60', 'entry-location' => 'Peaväljak'] as $type => $title) {
            if (! DB::table('racster_assets')->where('type', $type)->whereNull('deleted_at')->exists()) {
                DB::table('racster_assets')->insert([
                    'uid' => 1, 'type' => $type, 'title' => $title,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    protected function ensureUser(Carbon $now, string $email, string $first, string $last, string $roles, $levelId): int
    {
        $existing = DB::table('users')->where('email', $email)->whereNull('deleted_at')->first();
        $payload = [
            'name' => $first.' '.$last,
            'first_name' => $first,
            'last_name' => $last,
            'mobile_country_code' => '+372',
            'mobile_number' => '500'.random_int(1000, 9999),
            'birthday' => '1990-01-01',
            'user_roles' => $roles,
            'user_level' => $roles === '4' ? $levelId : null,
            'email_verified_at' => $now,
            'terms_accepted' => 1,
            'updated_at' => $now,
        ];
        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update($payload + [
                'password' => Hash::make(self::PASSWORD),
            ]);
            return (int) $existing->id;
        }

        return (int) DB::table('users')->insertGetId($payload + [
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'created_at' => $now,
        ]);
    }

    protected function ensureCredit(int $userId, int $target): void
    {
        $balance = (int) DB::table('racster_user_transactions')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type='added' THEN transaction_amount ELSE 0 END),0) - COALESCE(SUM(CASE WHEN transaction_type='used' THEN transaction_amount ELSE 0 END),0) as b")
            ->value('b');

        if ($balance < $target) {
            DB::table('racster_user_transactions')->insert([
                'creator_id' => $userId,
                'transaction_type' => 'added',
                'transaction_amount' => $target - $balance,
                'transaction_comment' => 'E2E testikrediit',
                'date_id' => null,
                'user_id' => $userId,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Vaene klient nulli: kustuta tema added/use tehingud (E2E kuupäevadeta)
        $poorId = DB::table('users')->where('email', self::POOR_EMAIL)->whereNull('deleted_at')->value('id');
        if ($poorId) {
            DB::table('racster_user_transactions')->where('user_id', $poorId)->delete();
        }
    }

    protected function fillDateWithDummies(int $entryId, int $dateId, Carbon $now, int $count): void
    {
        $existing = DB::table('racster_entry_users')
            ->where('date_id', $dateId)
            ->where('user_type', 'client')
            ->whereNull('deleted_at')
            ->count();

        for ($i = $existing; $i < $count; $i++) {
            $email = "e2e-dummy{$dateId}-{$i}@racster.com";
            $uid = DB::table('users')->where('email', $email)->value('id');
            if (! $uid) {
                $uid = DB::table('users')->insertGetId([
                    'name' => "Dummy $i",
                    'email' => $email,
                    'password' => Hash::make(self::PASSWORD),
                    'first_name' => 'Dummy',
                    'last_name' => (string) $i,
                    'user_roles' => '4',
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            DB::table('racster_entry_users')->insert([
                'creator_id' => $uid,
                'entry_id' => $entryId,
                'date_id' => $dateId,
                'user_type' => 'client',
                'user_id' => $uid,
                'user_quantity' => 1,
                'paying' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('racster_entry_dates')->where('id', $dateId)->update([
            'client_count' => $count,
            'updated_at' => $now,
        ]);
    }
}
