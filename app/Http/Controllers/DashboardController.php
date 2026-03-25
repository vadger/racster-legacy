<?php

namespace App\Http\Controllers;

use DB;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;

class DashboardController extends Controller
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
     * Show user dashboard
     */
    public function showUserDashboard()
	{

		// Define assets list array
		$asset_list = [];

		// Get all assets
		$assets = DB::table('racster_assets')
			->whereIn('type', array_merge(array_diff(array_keys(config('assets.active')), config('assets.hidden')), ['asset-translation']))
			->whereNull('deleted_at')
			->orderByRaw("CAST(extra AS UNSIGNED)")
			->orderBy('title', 'asc')
			->get();

		// Create predefined arrays of assets
		foreach ($assets as $asset){

			// Add translations to assets list
			if ($asset->type == 'asset-translation'){

				$asset_list['langs'][$asset->parent][$asset->extra] = [
					'string' => $asset->title,
					'description' => $asset->descr,
				];

			}else{

				// Add assets by Type & by ID to asset array
				$asset_list['bytype'][$asset->type][] = $asset;
				$asset_list['byid'][$asset->id] = $asset;

			}

		}

		// Get user next entry info
		$next_entry = \App\Http\Controllers\TimetableController::getUserNextEntry();

		// Get user transactions balance
		$transactions_balance = \App\Models\UserTransactions::getBalance();

		return view('dashboard', [
			'assets' => $asset_list,
			'next_entry' => (!empty($next_entry) ? $next_entry : ''),
			'balance' => (!empty($transactions_balance) ? $transactions_balance : 0),
		]);

	}

}
