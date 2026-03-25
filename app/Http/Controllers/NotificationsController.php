<?php

namespace App\Http\Controllers;

use DB;
use Auth;

use App\Models\NotificationFilter;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;

class NotificationsController extends Controller
{

	/**
	 * Create a new controller instance.
	 */
	public function __construct()
	{

		$this->thistime = time();
		$this->perlist = 20;

	}

	/**
	 * Ensure each user has exactly one filter record
	 */
	protected function getOrCreateFilterForUser($userid)
	{

		return NotificationFilter::firstOrCreate(
			['user_id' => $userid],
			['name' => 'Default notifications', 'is_active' => true]
		);

	}

	/**
	 * Show user notifications
	 */
	public function manageUserNotifications(Request $request)
	{

		$filters = $this->getOrCreateFilterForUser(Auth::user()->id);

		// Define assets list array
		$asset_list = [];

		// Get all assets
		$assets = DB::table('racster_assets as asset')
			->select([
				'asset.id',
				'asset.type',
				DB::raw('COALESCE(trans.title, asset.title) as title'),
				DB::raw('COALESCE(trans.descr, asset.descr) as descr'),
				'asset.extra',
			])
			->leftJoin('racster_assets as trans', function ($join) {
				$join->on('trans.parent', '=', 'asset.id');
				$join->where('trans.type', '=', 'asset-translation');
				$join->where('trans.extra', app()->getLocale());
				$join->whereNull('trans.deleted_at');
			})
			->whereIn('asset.type', [
				'entry-type',
				'entry-location',
			])
			->whereNull('asset.deleted_at')
			->orderByRaw("CAST(asset.extra AS UNSIGNED)")
			->orderBy(DB::raw('COALESCE(trans.title, asset.title)'), 'asc')
			->get();

		// Create predefined arrays of assets by type & ID
		foreach ($assets as $asset){
			$asset_list['bytype'][$asset->type][] = $asset;
			$asset_list['byid'][$asset->id] = $asset;
		}

		 // Get coaches related to entries
		$coaches = DB::table('users')
			->selectRaw("id, IFNULL(NULLIF(CONCAT(first_name, ' ', last_name), ' '), name) as display_name")
			->where(function ($query) {
				$query->whereRaw("find_in_set(".config('racster.coaches_role').", replace(user_roles, '|', ',')) > 0");
				if (Auth::user()->hasRole('admin')){
					$query->orWhereIn('id', config('racster.superadmin'));
				}
			})
			->whereNull('deleted_at')
			->orderBy('display_name', 'asc')
			->pluck('display_name', 'id')
			->toArray();

		return view('notifications.manageUserNotifications', [
			'filters' => $filters,
			'assets' => $asset_list,
			'coaches' => (!empty($coaches) ? $coaches : ''),
		]);

	}

	/**
	 * Toggle notifications activation
	 */
	public function toggleNotificationsActivation(Request $request)
	{

		$filter = NotificationFilter::firstOrCreate(
			['user_id' => Auth::user()->id],
			['is_active' => true]
		);

		$filter->is_active = !$filter->is_active;
		$filter->save();

		return response()->json([
			'success' => true,
			'is_active' => $filter->is_active,
		]);

	}

	/**
	 * Update the user notification selections
	 */
	public function updateNotificationFilters(Request $request)
	{

		$filter = $this->getOrCreateFilterForUser(Auth::user()->id);

		$data = $request->validate([
			'coach_ids'				=> ['array'],
			'coach_ids.*'			=> ['integer', 'exists:users,id'],
			'location_ids'			=> ['array'],
			'location_ids.*'		=> ['integer', 'exists:racster_assets,id'],
			'training_type_ids'		=> ['array'],
			'training_type_ids.*'	=> ['integer', 'exists:racster_assets,id'],
		]);

		$filter->coaches()->sync($data['coach_ids'] ?? []);
		$filter->locations()->sync($data['location_ids'] ?? []);
		$filter->trainingTypes()->sync($data['training_type_ids'] ?? []);

		return response()->json([
			'success' => true,
			'msg' => trans('racster.notification-filters-successfully-updated'),
		]);

	}

}
