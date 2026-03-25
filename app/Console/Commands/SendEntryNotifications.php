<?php

namespace App\Console\Commands;

use Log;
use LaravelLocalization;

use App\Models\RacsterEntryDate;
use App\Models\ScheduledEmail;
use App\Services\EntryNotificationService;

use Carbon\Carbon;

use Illuminate\Console\Command;

class SendEntryNotifications extends Command
{

	protected $signature = 'notifications:entries';
	protected $description = 'Send notifications for upcoming entry dates one email per user';

	public function handle(EntryNotificationService $service): int
	{

		$now = Carbon::now();

		// Get all not notified future dates
		$dates = RacsterEntryDate::query()
			->whereNull('notified_at')
			->whereBetween('entry_start', [$now, $now->copy()->addDays(config('racster.notification-days-count'))])
            ->whereHas('entry', function ($q) {
                $q->whereColumn('client_count', '<', 'client_limit');
            })
			->where('private_entry', '!=', 1)
			->with('entry')
			->get();

		if ($dates->isEmpty()) {
			Log::channel('mailnotices')->info('No upcoming entry dates to notify.', [
				'starting' => $now->format('d.m.Y'),
				'ending' => $now->copy()->addDays(config('racster.notification-days-count'))->format('d.m.Y'),
			]);
			return self::SUCCESS;
		}

		// Define user dates array
		$userDates = [];

		// Collect all dates for users
		foreach ($dates as $date) {

			$entry = $date->entry;

			if (!$entry) {
				continue;
			}

			$users = $service->usersToNotifyForEntryDate($entry, $date);

			if ($users->isEmpty()) {

				Log::channel('mailnotices')->info('No users to notify for entry date', [
					'date' => date('d.m.Y', strtotime($date->entry_start)),
					'dateID' => $date->id,
				]);

				// Add notification sending time
				$date->notified_at = Carbon::now();

				$date->save();

				continue;

			}

			foreach ($users as $user) {

				Log::channel('mailnotices')->info('Notify user of entry date', [
					'userID' => $user->id,
					'date' => date('d.m.Y', strtotime($date->entry_start)),
					'dateID' => $date->id,
				]);

				if (!isset($userDates[$user->id])) {
					$userDates[$user->id] = [
						'user' => $user,
						'items' => [],
					];
				}

				$userDates[$user->id]['items'][] = [
					'entry' => $entry,
					'date' => $date,
				];

			}

			// Add notification sending time
			$date->notified_at = Carbon::now();

			$date->save();

		}

        // Send notification to user with all available dates
        foreach ($userDates as $bucket) {

            $user  = $bucket['user'];
            $items = $bucket['items'];

            if (empty($items)) {
                continue;
            }

			// Define available dates array
			$available_dates = [];

			// Add available dates to array
			foreach ($items as $item) {

				$available_dates[] =
					'<li>'.
						trans('racster.available-entry-dates-notification-email-content-date', [
							'title' => $item['entry']->entry_title ?? ('Entry #' . $item['entry']->id),
							'date' => date('d.m.Y', strtotime($item['date']->entry_start)),
							'openlink' => LaravelLocalization::localizeUrl('/time/'.strtotime($item['date']->entry_start)).'#entry'.$item['date']->id,
						]).
					'</li>';

			}

			// Schedule email for user with(out) stripe invoice
			ScheduledEmail::create([
				'user_id'	=> $user->id,
				'to_email'	=> $user->email,
				'subject'	=> trans('racster.available-entry-dates-notification-email-subject'),
				'body'		=> trans('racster.available-entry-dates-notification-email-content', ['dateslist' => implode('', $available_dates)]),
				'send_at'	=> Carbon::createFromTimestamp(strtotime('+1 minutes'))->setTimezone(config('app.timezone')),
				'status'	=> 'pending',
			]);

			Log::channel('mailnotices')->info('Sent notification to user', [
				'userID' => $user->id,
				'entries' => count($items),
			]);

        }

		Log::channel('mailnotices')->info('Notifications processed.');

		return self::SUCCESS;

	}

}
