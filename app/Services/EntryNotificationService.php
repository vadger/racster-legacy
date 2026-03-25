<?php

namespace App\Services;

use App\Models\RacsterEntry;
use App\Models\RacsterEntryDate;
use App\Models\NotificationFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EntryNotificationService
{

	/**
     * Get users who want notifications for this entry + date,
     * excluding users who are already clients on this entry,
     * and only for matching client level visibility.
	 */
	public function usersToNotifyForEntryDate(RacsterEntry $entry, RacsterEntryDate $entryDate): Collection
	{

		// Get entry coach IDs
		$coachIds = DB::table('racster_entry_users')
			->where('entry_id', $entry->id)
			->where('user_type', 'coach')
			->whereNull('deleted_at')
			->pluck('user_id');

		// Get entry client IDs
		$clientIds = DB::table('racster_entry_users')
			->where('entry_id', $entry->id)
			->where('user_type', 'client')
			->whereNull('deleted_at')
			->pluck('user_id')
			->toArray();

		// Find matching filters
		$filters = NotificationFilter::query()
			->where('is_active', true)
			->where(function ($q) use ($coachIds) {
				$q->whereDoesntHave('coaches')
					->orWhereHas('coaches', function ($q2) use ($coachIds) {
						if ($coachIds->isNotEmpty()) {
							$q2->whereIn('users.id', $coachIds);
						}
					});
			})
			->where(function ($q) use ($entryDate) {
				$q->whereDoesntHave('locations')
					->orWhereHas('locations', function ($q2) use ($entryDate) {
						$q2->where('racster_assets.id', $entryDate->entry_location);
					});
			})
			->where(function ($q) use ($entry) {
				$q->whereDoesntHave('trainingTypes')
					->orWhereHas('trainingTypes', function ($q2) use ($entry) {
						$q2->where('racster_assets.id', $entry->entry_type);
					});
			})
			->with('user')
			->get();

		// Collect unique users and EXCLUDE existing clients
		return $filters
			->pluck('user')
			->filter()
			->reject(function ($user) use ($clientIds) {
				return in_array($user->id, $clientIds, true);
			})
            ->filter(function ($user) use ($entry) {
                $userLevel = $user->user_level;
                $groups = config("racster.client-levels.$userLevel", []);
                return is_null($entry->client_level)
                    || $entry->client_level == $userLevel
                    || in_array($entry->client_level, $groups, true);
            })
			->unique('id')
			->values();

	}

}
