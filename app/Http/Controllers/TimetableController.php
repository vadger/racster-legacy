<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use Validator;
use Storage;
use Session;
use Lang;
use Response;
use LaravelLocalization;
use Log;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

use App\Models\Product;
use App\Models\ScheduledEmail;
use App\Models\UserPayment;
use App\Models\UserTransactions;
use App\Services\SubscriptionEndService;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Laravel\Cashier\Subscription as CashierSubscription;

class TimetableController extends Controller
{

	/**
	 * Create a new controller instance.
	 */
	public function __construct()
	{

		$this->thistime = time();

		$this->entry_rules = [ // Define entry data validation
			'entry_title'		=> 'required|max:255',
			'entry_description'	=> 'max:1000',
			'entry_type'		=> 'required|integer',
			'client_level'		=> 'required|integer',
			'various_clients'	=> '|in:Y,',
			'recurring_entry'	=> '|in:Y,',
			'entry_monthly'		=> 'integer|nullable|required_if:recurring_entry,Y',
			'client_limit'		=> 'required|integer|min:1',
			'entry_coaches'		=> 'required|array|min:1',
			'entry_coaches.*'	=> 'required',
			'entry_start'		=> 'required|array|min:1',
			'entry_start.*'		=> 'required|date', // |after:now
			'entry_ending'		=> 'required_without:entry_length|array|min:1',
			'entry_ending.*'	=> 'required|date', // |after:now
			'entry_length'		=> 'required_without:entry_ending|array|min:1',
			'entry_length.*'	=> 'required|integer',
			'entry_location'	=> 'required|array|min:1',
			'entry_location.*'	=> 'required',
			'private_entry'		=> 'array',
			'private_entry.*'	=> 'required|in:Y,',
		];

		$this->recurring_rules = [ // Define recurring dates validation
			'recurring_day'		=> 'array',
			'recurring_specs'	=> 'required|in:'.implode(',', config('racster.recurring-specs')),
			'recurring_start'	=> 'required|date',
			'recurring_time'	=> 'required|date_format:H:i',
			'recurring_period'	=> 'required|date',
		];

	}

	/**
	 * Change timetable view
	 */
	public function changeView($form = null)
	{

		if (Auth::user()->hasRole('client')){

			if (!empty($form)){ Session::put('timetable-view', $form); }
			return Redirect::to(url()->previous());

		}else{ return view('nouser'); }

	}

	/**
	 * Change timetable time
	 */
	public function changeTime($time = null)
	{

		if (Auth::user()->hasRole('client')){

			if (!empty($time)){ Session::put('time', $time); }
			return Redirect::to(LaravelLocalization::localizeUrl('/timetable'));

		}else{ return view('nouser'); }

	}

	/**
	 * Send attendance notice with successful payment
	 */
	public static function sendAttendanceNotice($entry_date, $entry_price, $invoiceID = null, $userID = null)
	{

		// Get entry data
		$entry_data = DB::table('racster_entries')
			->where('id', $entry_date->entry_id)
			->whereNull('deleted_at')
			->first();

		// Get entry format title
		$entry_format = DB::table('racster_assets')
			->where(function ($query) use ($entry_data) {
				$query->where('id', $entry_data->entry_type);
				$query->orWhere(function ($subquery) use ($entry_data) {
					$subquery->where('type', 'asset-translation');
					$subquery->where('parent', $entry_data->entry_type);
					$subquery->where('extra', app()->getLocale());
				});
			})
			->whereNull('deleted_at')
			->orderByRaw("CASE WHEN type = 'asset-translation' THEN 0 ELSE 1 END")
			->limit(1)
			->value('title');

		// Get entry date location
		$entry_location = DB::table('racster_assets')
			->where(function ($query) use ($entry_date) {
				$query->where('id', $entry_date->entry_location);
				$query->orWhere(function ($subquery) use ($entry_date) {
					$subquery->where('type', 'asset-translation');
					$subquery->where('parent', $entry_date->entry_location);
					$subquery->where('extra', app()->getLocale());
				});
			})
			->whereNull('deleted_at')
			->orderByRaw("CASE WHEN type = 'asset-translation' THEN 0 ELSE 1 END")
			->limit(1)
			->value('title');

		// Get entry date location map link
		$entry_location_map = DB::table('racster_assets')
			->where('id', $entry_date->entry_location)
			->whereNull('deleted_at')
			->orderByRaw("CASE WHEN type = 'asset-translation' THEN 0 ELSE 1 END")
			->limit(1)
			->value('descr');

		// Get coaches related to entry
		$entry_coaches = DB::table('racster_entry_users')
			->selectRaw("users.id, IFNULL(NULLIF(CONCAT(users.first_name, ' ', users.last_name), ' '), users.name) as display_name")
			->leftJoin('users', function($join){
				$join->on('users.id', '=', 'racster_entry_users.user_id');
			})
			->where('racster_entry_users.entry_id', $entry_data->id)
			->whereNull('racster_entry_users.date_id')
			->where('racster_entry_users.user_type', 'coach')
			->whereNull('racster_entry_users.deleted_at')
			->orderBy('display_name', 'asc')
			->pluck('display_name')
			->toArray();

		// Define entry start and end
		$starting = strtotime($entry_date->entry_start);
		$ending = strtotime($entry_date->entry_ending);

		// Get entry length
		$minutes = (($ending-$starting)/60);
		$hours = intdiv($minutes, 60);
		$remaining = ($minutes % 60);

		// Get user info when user ID is set
		if (!empty($userID)){
			$userInfo = DB::table('users')->where('id', $userID)->whereNull('deleted_at')->first();
		}

		// Define minimal minutes for cancelling entry date
		$cancelling_min_period = DB::table('racster_assets')
			->where('type', 'entry-mincancel')
			->where('parent', $entry_data->entry_type)
			->whereNull('deleted_at')
			->value('title');

		// Define cancellation date
		$cancellation_date = strtotime('-'.(!empty($cancelling_min_period) ? (int)$cancelling_min_period : config('racster.cancelling-min-period')).' minutes', strtotime($entry_date->entry_start));
		$cancellation_string = Carbon::createFromTimestamp($cancellation_date)->setTimezone(config('app.timezone'))->locale(app()->getLocale());

		// Schedule email for user with(out) stripe invoice
		ScheduledEmail::create([
			'user_id'		=> ((!empty($userID) and !empty($userInfo->id)) ? $userInfo->id : Auth::user()->id),
			'to_email'		=> ((!empty($userID) and !empty($userInfo->id)) ? $userInfo->email : Auth::user()->email),
			'subject'		=> trans('racster.attending-to-entry-email-subject', ['start' => date('d.m H:i', $starting)]),
			'body'			=> trans('racster.attending-to-entry-email-content'.(date('d.m.Y', $starting) != date('d.m.Y', $ending) ? '-several-days' : ''), [
				'start' => date('d.m.Y H:i', $starting),
				'ending' => date('d.m.Y H:i', $ending),
				'length' => ($hours > 0 ? $hours.trans('racster.hour-short') : '').($remaining > 0 ? ($hours > 0 ? ' ' : '').$remaining.trans('racster.minutes-short') : ''),
				'format' => $entry_format,
				'price' => $entry_price,
				'location' => $entry_location,
				'maplink' => $entry_location_map,
				'coaches' => implode(', ', $entry_coaches),
			]).
				'<br /><hr />'.
				'<p style="margin-bottom:0;padding-top:12px;text-align:center;">'.
					'<em>'.
						trans('racster.cancellation-available-until', ['time' => $cancellation_string->translatedFormat((app()->getLocale() === 'et' ? 'd.m \\k\\e\\l\\l H:i' : 'F jS \\a\\t g:i A'))]).'<br />'.
						trans('racster.on-time-cancellation-gives-you-credit').
					'</em>'.
				'</p>',
			'heading'		=> trans('racster.attending-to-entry-email-heading', [
				'title' => $entry_data->entry_title,
			]),
			'cta_name'		=> trans('racster.cancel'),
			'cta_name'		=> trans('racster.cancel'),
			'cta_link'		=> LaravelLocalization::localizeUrl('/time/'.$starting).'#entry'.$entry_date->id,
			'stripe_invid'	=> $invoiceID,
			'send_at'		=> Carbon::createFromTimestamp(strtotime('+1 minutes'))->setTimezone(config('app.timezone')),
			'status'		=> 'pending',
		]);

	}
	 

	/**
	 * Attend client to timetable entry period
	 */
	public function attendToEntryPeriod(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('client') and !empty($request->input('eid'))){

			// Get entry date data
			$entry_date = DB::table('racster_entry_dates')
				->where('id', $request->input('eid'))
				->whereNull('deleted_at')
				->first();

			if (!empty($entry_date)){

				// Check if client is attending or not
				$cid = DB::table('racster_entry_users')
					->where('entry_id', $entry_date->entry_id)
					->where('date_id', $entry_date->id)
					->where('user_type', 'client')
					->where('user_id', Auth::user()->id)
					->whereNull('deleted_at')
					->value('id');

				if (!empty($cid)){

					// Get client quantity for the entry date
					$client_quantity = DB::table('racster_entry_users')
						->where('id', $cid)
						->whereNull('deleted_at')
						->value('user_quantity');

				}else{

					// Get client quantity if selected
					$client_quantity = ((empty($request->input('cqty')) or $request->input('cqty') < 1) ? 1 : $request->input('cqty'));

				}

				// Do not allow overbooking
				if (($entry_date->client_count+$client_quantity) > $entry_date->client_limit and empty($cid)){

					return response()->json([
						'success' => true,
						'full' => true,
						'msg' => trans('racster.participant-limit-exceeded'),
					]);

				}else{

					// Define entry price
					$entry_price = (!empty($entry_date->entry_price) ? $entry_date->entry_price : 0);

					// Add discount if client has discount
					if (!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry_date->extra_price != 1){
						$entry_price = $entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0);
					}

					// Multiply the entry price with user quantity / client limit with private event
					if ($request->input('private') == 'true' and $entry_date->client_limit > 1){
						$entry_price = $entry_date->client_limit*$entry_price;
					}else{
						$entry_price = $client_quantity*$entry_price;
					}

					if (!empty($cid)){

						// Check if client transaction exists
						$transaction = UserTransactions::getUserDateTransaction(['onhold'], $entry_date->id);

						if (!empty($transaction) and !empty($transaction->stripe_sess_id)){

							// Find the pending payment row by session id
							$payment = UserPayment::where('stripe_checkout_session_id', $transaction->stripe_sess_id)->first();

							if ($payment){
								$payment->markCancelled();
							}

						}else{

							// Check if user is paying for the entry date
							$paying = DB::table('racster_entry_users')
								->where('id', $cid)
								->whereNull('deleted_at')
								->value('paying');

							// Define updatable date data
							$datedata = [
								'client_count' => (!empty($entry_date->client_count) ? ($entry_date->client_count-$client_quantity) : NULL),
								'updated_at' => Carbon::now(),
							];

							// Make private event public without clients
							if (empty($entry_date->client_count) or ($entry_date->client_count-$client_quantity) < 1){
								$datedata['private_entry'] = 0;
							}

							// Update client count in entry date row
							DB::table('racster_entry_dates')
								->where('id', $entry_date->id)
								->whereNull('deleted_at')
								->update($datedata);

							// Cancel client attendance
							DB::table('racster_entry_users')
								->where('id', $cid)
								->whereNull('deleted_at')
								->update([
									'updated_at'	=> Carbon::now(),
									'deleted_at'	=> Carbon::now(),
								]);

							
							// Add credit back to paying client transactions
							if (!empty($paying) and $paying == 1){

								// Get user date transactions balance
								$transaction = DB::table('racster_user_transactions')
									->where('transaction_type', 'used')
									->where('date_id', $entry_date->id)
									->where('user_id', Auth::user()->id)
									->whereNull('deleted_at')
									->orderBy('updated_at', 'DESC')
									->first();

								UserTransactions::createTransaction('added', [
									'amount'	=> ((!empty($transaction->transaction_amount) and $transaction->transaction_amount > 0) ? $transaction->transaction_amount : $entry_price),
									'comment'	=> trans('racster.transaction-cancelling-attendance-comment'),
									'date_id'	=> $entry_date->id,
									'user_id'	=> Auth::user()->id,
								]);

							}

						}

					}else{

						// Get user transactions balance
						$transactions_balance = UserTransactions::getBalance();

						// Define updatable date data
						$datedata = [
							'client_count' => (!empty($entry_date->client_count) ? ($entry_date->client_count+$client_quantity) : $client_quantity),
							'updated_at' => Carbon::now(),
						];

						// If user wants private event
						if ($request->input('private') == 'true'){
							$datedata['private_entry'] = 1;
						}

						// Update client count in entry date row
						DB::table('racster_entry_dates')
							->where('id', $entry_date->id)
							->whereNull('deleted_at')
							->update($datedata);

						// Add new client to entry
						DB::table('racster_entry_users')
							->insert([
								'creator_id'	=> Auth::user()->id,
								'entry_id'		=> $entry_date->entry_id,
								'date_id'		=> $entry_date->id,
								'user_type'		=> 'client',
								'user_id'		=> Auth::user()->id,
								'user_quantity'	=> $client_quantity,
								'paying'		=> 1,
								'created_at'	=> Carbon::now(),
								'updated_at'	=> Carbon::now(),
							]);

						// Add client transaction
						$transaction = UserTransactions::createTransaction(($transactions_balance >= $entry_price ? 'used' : 'onhold'), [
							'amount'	=> $entry_price,
							'comment'	=> trans('racster.transaction-attending-comment'),
							'date_id'	=> $entry_date->id,
							'user_id'	=> Auth::user()->id,
						]);

						// Make Stripe payment if credit is not enough
						if ($transactions_balance < $entry_price){

							// Use credit as a partial payment
							if ($request->input('credit') == 'true'){
								$entry_price = $entry_price-$transactions_balance;
							}

							// Get first active oneoff product
							$product = Product::query()
								->active()
								->oneOff()
								->whereHas('oneOffPrices')
								->with('oneOffPrices')
								->first();

							// Create Stripe checkout url
							$payment_url = app(\App\Http\Controllers\CheckoutController::class)
								->oneOff(
									request(),
									$product,
									amount: ($entry_price*100),
									currency: strtolower(config('racster.main-currency')),
									entryId: $entry_date->entry_id,
									dateId: $entry_date->id,
									transactionId: $transaction->id,
									redir: false
								);

							if (!empty($payment_url)){

								return response()->json([
									'success' => true,
									'pay_url' => $payment_url,
									'msg' => trans('racster.participation-saved-successfully'),
								]);

							}

						}

						// Send notice to user with entry info
						if (!empty(Auth::user()->email)){

							$this->sendAttendanceNotice($entry_date, $entry_price);

						}

					}

					return response()->json([
						'success' => true,
						'entry' => $entry_date,
						'msg' => trans('racster.participation-'.(!empty($cid) ? 'cancelled' : 'saved').'-successfully'),
					]);

				}

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Acquire timetable entry price
	 */
	public function acquireTimetableEntryPrice(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('client') and !empty($request->input('did'))){

			// Get entry date data
			$entry_date = DB::table('racster_entry_dates')
				->where('id', $request->input('did'))
				->whereNull('deleted_at')
				->first();

			if (!empty($entry_date)){

				// Define entry price
				$entry_price = (!empty($entry_date->entry_price) ? round($entry_date->entry_price, 0) : config('racster.default-price'));

				// Multiply the entry price with client limit if user wants private event
				if ($request->input('private') == 'true' and $entry_date->client_limit > 1){
					$entry_price = $entry_date->client_limit*$entry_price;
				}

				return response()->json([
					'success' => true,
					'price' => $entry_price.config('racster.transaction-token'),
					'userprice' => ((!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry_date->extra_price != 1) ?
						$entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0).config('racster.transaction-token') : ''),
					'msg' => trans('racster.participation-saved-successfully'),
				]);

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Acquire timetable entry data
	 */
	public function acquireTimetableEntryInfo(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('client') and !empty($request->input('eid'))){

			// Get entry date data
			$entry_date = DB::table('racster_entry_dates')
				->where('id', $request->input('eid'))
				->whereNull('deleted_at')
				->first();

			if (!empty($entry_date)){

				// Get entry data
				$entry_data = DB::table('racster_entries')
					->where('id', $entry_date->entry_id)
					->whereNull('deleted_at')
					->first();

				if (!empty($entry_data)){

					// Define assets list array
					$asset_list = [];

					// Get all assets
					$assets = DB::table('racster_assets')
						->where(function ($query) use ($entry_data, $entry_date) {
							$query->orWhere(function ($subquery) use ($entry_data, $entry_date) {
								$subquery->whereIn('type', [
									'entry-type',
									'client-level',
									'entry-length',
									'entry-location',
								]);
								$subquery->whereIn('id', [
									$entry_data->entry_type,
									$entry_data->client_level,
									$entry_date->entry_length,
									$entry_date->entry_location,
								]);
							});
							$query->orWhere(function ($subquery) use ($entry_data, $entry_date) {
								$subquery->where('type', 'asset-translation');
								$subquery->whereIn('parent', [
									$entry_data->entry_type,
									$entry_data->client_level,
									$entry_date->entry_length,
									$entry_date->entry_location,
								]);
								$subquery->where('extra', app()->getLocale());
							});
						})
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

					// Get user transactions balance
					$transactions_balance = UserTransactions::getBalance();

					// Get users related to entry
					$entry_users = DB::table('racster_entry_users as user')
						->select('users.id', 'users.profile_image', 'users.user_desc', 'user.user_type as utype', 'user.paying as payd', 'user.user_quantity as uqty')
						->selectRaw("IFNULL(NULLIF(CONCAT(users.first_name, ' ', users.last_name), ' '), users.name) as display_name")
						->selectRaw("IFNULL(NULLIF(users.first_name, ' '), users.name) as client_name")
						->selectRaw("CONCAT(users.mobile_country_code, users.mobile_number) as phone")
						->leftJoin('users', function($join){
							$join->on('users.id', '=', 'user.user_id');
						})
						->where('user.entry_id', $entry_data->id)
						->where(function ($query) use ($entry_date) {
							$query->where('user.date_id', $entry_date->id);
							$query->orWhere('user.user_type', 'coach');
						})
						->whereIn('user.user_type', ['coach', 'client'])
						->whereNull('user.deleted_at')
						->whereNull('users.deleted_at')
						->orderBy('display_name', 'ASC')
						->get();

					// Define minimal minutes for attending entry date
					$attending_min_period = DB::table('racster_assets')
						->where('type', 'entry-minperiod')
						->where('parent', $entry_data->entry_type)
						->whereNull('deleted_at')
						->value('title');

					// Define minimal minutes for cancelling entry date
					$cancelling_min_period = DB::table('racster_assets')
						->where('type', 'entry-mincancel')
						->where('parent', $entry_data->entry_type)
						->whereNull('deleted_at')
						->value('title');

					// Define cancellation date
					$cancellation_date = strtotime('-'.(!empty($cancelling_min_period) ? (int)$cancelling_min_period : config('racster.cancelling-min-period')).' minutes', strtotime($entry_date->entry_start));
					$cancellation_string = Carbon::createFromTimestamp($cancellation_date)->setTimezone(config('app.timezone'))->locale(app()->getLocale());

					// Define entry price
					$entry_price = (!empty($entry_date->entry_price) ? round($entry_date->entry_price, 0) : config('racster.default-price'));

					// Check if user is attending on entry date
					$user_attending = DB::table('racster_entry_users')
						->where('entry_id', $entry_data->id)
						->where('date_id', $entry_date->id)
						->where('user_type', 'client')
						->where('user_id', Auth::user()->id)
						->whereNull('deleted_at')
						->count();

					// Get user transaction type if user is attending on entry date
					if ($user_attending > 0){
						$transaction = UserTransactions::getUserDateTransaction(['onhold'], $entry_date->id);
						if (!empty($transaction) and !empty($transaction->stripe_sess_id)){
							$paymentId = UserPayment::findIdBySessionId($transaction->stripe_sess_id);
						}elseif (!empty($entry_data->recurring_entry)){
							$activeSubscription = UserPayment::findByEntryID($entry_data->id);
							if (!empty($transaction->id) and empty($activeSubscription)){
								$paymentId = 'newsub';
							}
						}elseif (!empty($transaction->id) and empty($transaction->stripe_sess_id)){
							$paymentId = 'newone';
						}
					}

					// Get active subscription product interval
					if (!empty($entry_data->recurring_entry) and $entry_data->recurring_entry == 1 and !empty($entry_data->entry_monthly_fee)){

						$subscription = Product::query()
							->active()
							->subscription()
							->first();

					}

					// Define entry data array
					$entry_info = [
						'id'		=> $entry_date->id,
						'eid'		=> $entry_data->id,
						'title'		=> $entry_data->entry_title,
						'descr'		=> (!empty($entry_data->entry_description) ? $entry_data->entry_description : ''),
						'limit'		=> (!empty($entry_date->client_limit) ? $entry_date->client_limit : ''),
						'used'		=> (!empty($entry_date->client_count) ? $entry_date->client_count : ''),
						'type'		=> (
							(!empty($asset_list) and array_key_exists('langs', $asset_list) and array_key_exists($entry_data->entry_type, $asset_list['langs'])) ?
								$asset_list['langs'][$entry_data->entry_type][app()->getLocale()]['string'] :
									((!empty($asset_list) and array_key_exists($entry_data->entry_type, $asset_list['byid'])) ?
										$asset_list['byid'][$entry_data->entry_type]->title :
											$entry_data->entry_type)
						),
						'typedesc'		=> (
							(!empty($asset_list) and array_key_exists('langs', $asset_list) and array_key_exists($entry_data->entry_type, $asset_list['langs'])) ?
								(!empty($asset_list['langs'][$entry_data->entry_type][app()->getLocale()]['description']) ? $asset_list['langs'][$entry_data->entry_type][app()->getLocale()]['description'] : '') :
									((!empty($asset_list) and array_key_exists($entry_data->entry_type, $asset_list['byid'])) ?
										(!empty($asset_list['byid'][$entry_data->entry_type]->descr) ? $asset_list['byid'][$entry_data->entry_type]->descr : '') :
											$entry_data->entry_type)
						),
						'level'		=> (
							(!empty($asset_list) and array_key_exists('langs', $asset_list) and array_key_exists($entry_data->client_level, $asset_list['langs'])) ?
								$asset_list['langs'][$entry_data->client_level][app()->getLocale()]['string'] :
									((!empty($asset_list) and array_key_exists($entry_data->client_level, $asset_list['byid'])) ?
										$asset_list['byid'][$entry_data->client_level]->title :
											(!empty($entry_data->client_level) ? $entry_data->client_level : trans('racster.for-all-levels')))
						),
						'leveldesc'	=> (
							(!empty($asset_list) and array_key_exists('langs', $asset_list) and array_key_exists($entry_data->client_level, $asset_list['langs'])) ?
								(!empty($asset_list['langs'][$entry_data->client_level][app()->getLocale()]['description']) ? $asset_list['langs'][$entry_data->client_level][app()->getLocale()]['description'] : '') :
									((!empty($asset_list) and array_key_exists($entry_data->client_level, $asset_list['byid'])) ?
										(!empty($asset_list['byid'][$entry_data->client_level]->descr) ? $asset_list['byid'][$entry_data->client_level]->descr : '') :
											$entry_data->client_level)
						),
						'date'		=> (date('d.m.Y', strtotime($entry_date->entry_start)) == date('d.m.Y', strtotime($entry_date->entry_ending)) ? date('d.m.Y', strtotime($entry_date->entry_start)) : ''),
						'start'		=> date((date('d.m.Y', strtotime($entry_date->entry_start)) == date('d.m.Y', strtotime($entry_date->entry_ending)) ? '' : 'd.m.Y ').'H:i', strtotime($entry_date->entry_start)),
						'end'		=> date((date('d.m.Y', strtotime($entry_date->entry_start)) == date('d.m.Y', strtotime($entry_date->entry_ending)) ? '' : 'd.m.Y ').'H:i', strtotime($entry_date->entry_ending)),
						'duration'	=> (!empty($entry_date->entry_length) ? (
							(!empty($asset_list) and array_key_exists('langs', $asset_list) and array_key_exists($entry_date->entry_length, $asset_list['langs'])) ?
								(!empty($asset_list['langs'][$entry_date->entry_length][app()->getLocale()]['description']) ? $asset_list['langs'][$entry_date->entry_length][app()->getLocale()]['description'] : '') :
									((!empty($asset_list) and array_key_exists($entry_date->entry_length, $asset_list['byid'])) ?
										(!empty($asset_list['byid'][$entry_date->entry_length]->descr) ? $asset_list['byid'][$entry_date->entry_length]->descr : '') : '')
						) : ''),
						'location'	=> (!empty($entry_date->entry_location) ? (
							(!empty($asset_list) and array_key_exists('langs', $asset_list) and array_key_exists($entry_date->entry_location, $asset_list['langs'])) ?
								$asset_list['langs'][$entry_date->entry_location][app()->getLocale()]['string'] :
									((!empty($asset_list) and array_key_exists($entry_date->entry_location, $asset_list['byid'])) ?
										$asset_list['byid'][$entry_date->entry_location]->title : '')
						) : ''),
						'gmap'		=> (!empty($entry_date->entry_location) ? (
							(!empty($asset_list) and array_key_exists('langs', $asset_list) and array_key_exists($entry_date->entry_location, $asset_list['langs']) and !empty($asset_list['langs'][$entry_date->entry_location][app()->getLocale()]['description'])) ?
								$asset_list['langs'][$entry_date->entry_location][app()->getLocale()]['description'] :
								((!empty($asset_list) and array_key_exists($entry_date->entry_location, $asset_list['byid']) and !empty($asset_list['byid'][$entry_date->entry_location]->descr)) ?
									$asset_list['byid'][$entry_date->entry_location]->descr : '')
						) : ''),
						'privacy'	=> (!empty($entry_date->private_entry) ? $entry_date->private_entry : ''),
						'price'		=> $entry_price.config('racster.transaction-token'),
						'userprice' => ((!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry_date->extra_price != 1) ?
							$entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0).config('racster.transaction-token') : ''),
						'okcredit'	=> ($transactions_balance >= ((!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry_date->extra_price != 1) ? $entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0) : $entry_price)),
						'balance'	=> $transactions_balance,
						'users'		=> (!empty($entry_users) ? $entry_users : []),
						'attend'	=> ($user_attending > 0),
						'onhold'	=> (!empty($paymentId) ? $paymentId : ''),
						'recurring'	=> ((!empty($entry_data->recurring_entry) and $entry_data->recurring_entry == 1) ? true : false),
						'recprice'	=> ((!empty($entry_data->recurring_entry) and $entry_data->recurring_entry == 1 and !empty($entry_data->entry_monthly_fee)) ?
							round($entry_data->entry_monthly_fee, 0).config('racster.transaction-token').(!empty($subscription) ? ' / '.trans('stripe-products.interval-option-'.$subscription->interval) : '') : 0),
						'reccost'	=> ((!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and !empty($entry_data->recurring_entry) and $entry_data->recurring_entry == 1 and !empty($entry_data->entry_monthly_fee)) ?
							round($entry_data->entry_monthly_fee, 0)-round(($entry_data->entry_monthly_fee*Auth::user()->discount_amount/100), 0).config('racster.transaction-token').(!empty($subscription) ? ' / '.trans('stripe-products.interval-option-'.$subscription->interval) : '') : ''),
						'subscribed'=> (!empty($activeSubscription)),
						'pastentry'	=> ($cancellation_date <= $this->thistime),
						'available' => (strtotime('-'.(!empty($attending_min_period) ? (int)$attending_min_period : config('racster.attending-min-period')).' minutes', strtotime($entry_date->entry_start)) >= $this->thistime),
						'started'	=> (strtotime($entry_date->entry_start) <= $this->thistime),
						'limfuture'	=> ((
							strtotime($entry_date->entry_start) > Carbon::now()->startOfWeek()->addWeek()->addDays(config('racster.prebooking-limit'))->timestamp and
								!in_array($entry_data->entry_type, config('racster.unlimited-prebooking-limit')) and !Auth::user()->hasRole('coach')
							) ? true : false),
						'noprivate' => (in_array($entry_data->entry_type, config('racster.unlimited-prebooking-limit'))),
						'annulment'	=> trans('racster.cancellation-available-until'.(Auth::user()->hasRole('coach') ? (Auth::user()->hasRole('manager') ? '-admin' : '-coach') : ''), [
								'time' => $cancellation_string->translatedFormat((app()->getLocale() === 'et' ? 'd.m \\k\\e\\l\\l H:i' : 'F jS \\a\\t g:i A')),
							]).((!Auth::user()->hasRole('coach') or Auth::user()->hasRole('manager')) ? '<br />'.trans('racster.on-time-cancellation-gives-you-credit') : ''),
					];

					return response()->json([
						'success' => true,
						'entry' => $entry_info,
						'msg' => trans('racster.entry-info-acquired-successfully'),
					]);

				}

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Change user calendar settings
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function changeSettings(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('client') and !empty($request->input('setting'))){

			// Save new setting to user info
			DB::table('users')
				->where('id', Auth::user()->id)
				->update([
					'active_entries'	=> ((!empty($request->input('setting')) and $request->input('setting') != 'all') ? $request->input('setting') : NULL),
					'updated_at'		=> \Carbon\Carbon::now(),
				]);

			return response()->json([
				'success' => true,
			]);

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Filter timetable by filters
	 */
	public function filterTimetableBy(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('client') and !empty($request->input('filter')) and !empty($request->input('view'))){

			if (!empty($request->input('filterby'))){

				// Add new filter to session
				Session::put($request->input('view').'-filter-'.$request->input('filter'), $request->input('filterby'));

			}else{

				// Remove filter from session
				Session::forget($request->input('view').'-filter-'.$request->input('filter'));

			}

			return response()->json([
				'success' => true,
			]);

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Split events into day segments
	 */
	function splitIntoDaySegments($events): \Illuminate\Support\Collection
	{

		$out = collect();

		foreach ($events as $e) {

			$start = Carbon::parse($e->entry_start);
			$end = Carbon::parse($e->entry_ending);

			// Guard
			if ($end->lessThanOrEqualTo($start)) {
				continue;
			}

			// Ending on midnight should not make new segment
			$endsAtMidnight = $end->format('H:i:s') === '00:00:00';

			// Same day -> one segment
			if ($start->toDateString() === $end->toDateString()) {
				$seg = clone $e;
				$seg->_seg_start = $start->copy();
				$seg->_seg_end = $end->copy();
				$seg->_seg_first = true;
				$seg->_seg_last = true;
				$out->push($seg);
				continue;
			}

			// Make a segment per day
			$cursor = $start->copy();
			$isFirst = true;

			// Do not make ending segment for midnight end
			$endDateForLoop = $endsAtMidnight ? $end->copy()->subDay()->toDateString() : $end->toDateString();

			while ($cursor->toDateString() < $endDateForLoop) {
				$seg = clone $e;
				$seg->_seg_start = $cursor->copy();
				$seg->_seg_end = $cursor->copy()->endOfDay()->addSecond();
				$seg->_seg_first = $isFirst;
				$seg->_seg_last = false;
				$out->push($seg);

				$isFirst = false;
				$cursor = $cursor->copy()->addDay()->startOfDay();
			}

			// Create last segment
			$seg = clone $e;
			$seg->_seg_start = $cursor->copy();

			if ($endsAtMidnight) {
				$seg->_seg_end = $cursor->copy()->endOfDay()->addSecond();
			} else {
				$seg->_seg_end = $end->copy();
			}

			$seg->_seg_first = $isFirst;
			$seg->_seg_last = true;
			$out->push($seg);

		}

		return $out;

	}

	/**
	 * Assign overlap columns per day
	 */
	function applyOverlapLayoutPerDay($segments): \Illuminate\Support\Collection
	{

		$byDay = collect($segments)->groupBy(fn($e) => $e->_seg_start->toDateString());
		$out = collect();

		foreach ($byDay as $day => $items) {

			// Compute minutes and sort
			$items = $items->map(function ($e) {
				$e->_s = $e->_seg_start->hour * 60 + $e->_seg_start->minute;
				$e->_e = $e->_seg_end->hour * 60 + $e->_seg_end->minute;

				// If segment ends at "24:00-ish", clamp to 1440
				if ($e->_seg_end->toDateString() !== $e->_seg_start->toDateString()) {
					$e->_e = 1440;
				}

				$e->_s = max(0, min(1440, $e->_s));
				$e->_e = max(0, min(1440, $e->_e));
				return $e;
			})->sortBy('_s')->values();

			$active = [];
			$freeCols = [];
			$clusterId = 0;
			$clusterMaxCols = [];

			foreach ($items as $e) {

				// Remove finished from active
				$newActive = [];
				foreach ($active as $a) {
					if ($a->_e <= $e->_s) {
						$freeCols[] = $a->_col;
					} else {
						$newActive[] = $a;
					}
				}
				$active = $newActive;

				// New cluster if no overlaps continuing
				if (count($active) === 0) {
					$clusterId++;
					$freeCols = [];
				}

				sort($freeCols);
				$col = count($freeCols) ? array_shift($freeCols) : count($active);

				$e->_col = $col;
				$e->_cluster = $clusterId;

				$active[] = $e;

				$usedCols = collect($active)->max('_col') + 1;
				$clusterMaxCols[$clusterId] = max($clusterMaxCols[$clusterId] ?? 1, $usedCols);

			}

			foreach ($items as $e) {
				$e->_cols = $clusterMaxCols[$e->_cluster] ?? 1;
				$out->push($e);
			}

		}

		return $out;

	}

	/**
	 * Split events into month day segments
	 */
	function buildMonthEntriesByDay($entries, Carbon $rangeStart, Carbon $rangeEnd): array
	{

		$out = [];

		foreach ($entries as $e) {

			$start = Carbon::parse($e->entry_start);
			$end = Carbon::parse($e->entry_ending);

			if ($end->lessThanOrEqualTo($start)) continue;

			// If ends exactly at midnight, treat it as ending the previous day
			if ($end->isStartOfDay()) {
				$end = $end->copy()->subSecond();
			}

			// Clamp to the month view range
			$s = $start->copy()->max($rangeStart);
			$t = $end->copy()->min($rangeEnd);

			if ($t->lessThan($s)) continue;

			// Add to every day it touches
			$day = $s->copy()->startOfDay();
			$lastDay = $t->copy()->startOfDay();

			while ($day->lessThanOrEqualTo($lastDay)) {
				$key = $day->format('Y-n-j');
				$out[$key][] = $e;
				$day->addDay();
			}

		}

		return $out;

	}

	/**
	 * Display the timetable
	 */
	public function display(Request $request)
	{

		if (Auth::user()->hasRole('client')){

			if ($request->isMethod('post')){

				// Clear all set filters
				if ($request->has('clear-filters')){

					// Remove coach filter from session
					Session::forget('timetable-filter-coach');

					// Remove entry type filter from session
					Session::forget('timetable-filter-etype');

					// Remove location filter from session
					Session::forget('timetable-filter-location');

					return Redirect::to(LaravelLocalization::localizeUrl('/timetable'));

				}

			}

			$this->viewform = (Session::has('timetable-view') ? Session::get('timetable-view') : 'week'); // Define the form of timetable
			$this->viewtime = (Session::has('time') ? Session::get('time') : $this->thistime); // Define the time of the view

			$caltime = []; // Define calendar times array

			$caltime['topd'] = strtotime('first day of this month 00:00:00', $this->viewtime); // Define the first day of the "moment" month
			$caltime['endd'] = strtotime('last day of this month 23:59:59', $this->viewtime); // Define the last day of the "moment" month

			$caltime['prev'] = strtotime('-1 month noon', $caltime['topd']); // Define the previous month of the "moment" month
			$caltime['next'] = strtotime('+1 month noon', $caltime['topd']); // Define the next month of the "moment" month
			$caltime['divy'] = strtotime('-1 year noon', $caltime['topd']); // Define the previous year of the "moment" month
			$caltime['addy'] = strtotime('+1 year noon', $caltime['topd']); // Define the next year of the "moment" month

			$caltime['topw'] = strtotime('monday this week 00:00:00', $this->viewtime); // Define the first day of the "moment" week
			$caltime['endw'] = strtotime('sunday this week 23:59:59', $this->viewtime); // Define the last day of the "moment" week
			$caltime['prevw'] = strtotime('-1 week noon', $caltime['topw']); // Define the previous week of the "moment" week
			$caltime['nextw'] = strtotime('+1 week noon', $caltime['topw']); // Define the next week of the "moment" week

			$caltime['dcnt'] = (date('w', $caltime['topd']) == 0 ? 1-(7-date('w', $caltime['topd'])) : 1-date('w', $caltime['topd'])); // Define what day in the week is the first of the "moment" month

			$caltime['nowd'] = $this->thistime; // Define "today"

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

			// Define selected period entries query
			$entries_query = DB::table('racster_entry_dates as date');

			// Define entries data selection
			$entries_query
				->select(
					'date.*',
					'entry.entry_title',
					'entry.entry_description',
					'entry.entry_type',
					'entry.client_level',
					'entry.recurring_entry',
					'client.id as active_user_row'
				)
				->selectSub(function ($q) {
					$q->from('racster_user_transactions as trans')
						->select('trans.id')
						->whereColumn('trans.date_id', 'date.id')
						->whereColumn('trans.user_id', 'client.user_id')
						->where('trans.transaction_type', 'onhold')
						->whereNull('trans.deleted_at')
						->limit(1);
				}, 'onhold');

			// Add additional tables
			$entries_query
				->leftJoin('racster_entries as entry', function($join){
					$join->on('entry.id', '=', 'date.entry_id');
					$join->whereNull('entry.deleted_at');
				});

			// Add additional tables
			$entries_query
				->leftJoin('racster_entry_users as client', function($join){
					$join->on('client.entry_id', '=', 'date.entry_id');
					if (Auth::user()->hasRole('coach') and !Auth::user()->hasRole('manager')){
						$join->where('client.user_type', 'coach');
					}else{
						$join->on('client.date_id', '=', 'date.id');
						$join->where('client.user_type', 'client');
					}
					$join->where('client.user_id', Auth::user()->id);
					$join->whereNull('client.deleted_at');
				});

			// Add date based conditions
			if ($this->viewform == 'week'){

				// Define week based starting and ending dates
				$entries_query
					->where('date.entry_start', '<', Carbon::createFromTimestamp($caltime['endw']))
					->where('date.entry_ending', '>', Carbon::createFromTimestamp($caltime['topw']));

			}else{

				// Define calendar based starting and ending dates
				$entries_query
					->where('date.entry_start', '<', Carbon::createFromTimestamp(strtotime('sunday this week 23:59:59', $caltime['endd'])))
					->where('date.entry_ending', '>', Carbon::createFromTimestamp(strtotime('monday this week 00:00:00', $caltime['topd'])));

			}

			// Limit entries by coach filter
			if (Session::has('timetable-filter-coach')){
				$entries_query
					->whereExists(function ($query) {
						$query->select(DB::raw(1))
							->from('racster_entry_users')
								->whereColumn('racster_entry_users.entry_id', 'date.entry_id')
								->where('racster_entry_users.user_type', 'coach')
								->where('racster_entry_users.user_id', Session::get('timetable-filter-coach'))
								->whereNull('racster_entry_users.deleted_at');
					});
			}

			// Limit entries by entry type filter
			if (Session::has('timetable-filter-etype')){
				$entries_query
					->where('entry.entry_type', Session::get('timetable-filter-etype'));
			}

			// Limit entries by entry location filter
			if (Session::has('timetable-filter-location')){
				$entries_query
					->where('date.entry_location', Session::get('timetable-filter-location'));
			}

			// Limit events for client
			if (!Auth::user()->hasRole('coach') or !empty(Auth::user()->active_entries)){

				// Add client access checking
				$entries_query
					->where(function ($query) {

						if (empty(Auth::user()->active_entries) or Auth::user()->active_entries != 'mine'){
							$query->whereNotNull('client.id');
						}
						if (empty(Auth::user()->active_entries) or Auth::user()->active_entries != 'free'){
							$query->orWhere(function ($subquery) {

								// Open are without client ID
								$subquery->whereNull('client.id');

								// Show only public entries what have spots
								$subquery->where('date.private_entry', '!=', 1);
								$subquery->whereRaw('((date.client_limit - date.client_count) > 0 OR date.client_count IS NULL)');

								// Add restriction only for clients
								if (Auth::user()->hasRole(['client'])){

									// Show only future free dates for clients
									$subquery->where('date.entry_start', '>', Carbon::now());

									// Show entries base on client level
									$userLevel = Auth::user()->user_level;
									$groups = config("racster.client-levels.$userLevel", []);
									$subquery->where(function ($lvlquery) use ($userLevel, $groups) {
										$lvlquery->whereNull('entry.client_level');
										$lvlquery->orWhere('entry.client_level', $userLevel);
										if ($groups) {
											$lvlquery->orWhereIn('entry.client_level', $groups);
										}
									});

								}

							});
						}

					});

			}

			// Limit events for coach
			if (Auth::user()->hasRole('coach') and !Auth::user()->hasRole('manager')){

				// Add client access checking
				$entries_query
					->where(function ($query) {
						$query->whereNotNull('client.id');
					});

			}

			// Add main conditions
			$entries_query
				->whereNull('date.deleted_at')
				->whereNull('entry.deleted_at');

			// Add ordering
			$entries_query
				->orderBy('date.entry_start')
				->orderBy('date.entry_ending');

			// Get selected period entries
			$entries = $entries_query
				->get();

			// Define entries and events arrays
			$entries_list = []; $eventsForGrid = [];

			// Add entries to manageable array with date as key
			if (!empty($entries)){

				if ($this->viewform == 'week'){

					// Get separate entries as events if duplicate would exist
					$events = collect($entries)->unique('id')->values();

					// Split events into day segments
					$segments = $this->splitIntoDaySegments($events);

					// Assign overlap columns per day
					$eventsForGrid = $this->applyOverlapLayoutPerDay($segments);

					foreach ($entries as $entry){
						$entries_list[date('Y-n-j'.($this->viewform == 'week' ? '-H-i' : ''), strtotime($entry->entry_start))][] = $entry;
					}

				}else{

					// Get only unique entries if duplicate would exist
					$entries = collect($entries)->unique('id')->values();

					// Define start and end time of month
					$rangeStart = Carbon::createFromTimestamp(strtotime('monday this week 00:00:00', $caltime['topd']));
					$rangeEnd = Carbon::createFromTimestamp(strtotime('sunday this week 23:59:59', $caltime['endd']));

					// Create entries for each day if overnight
					$entries_list = $this->buildMonthEntriesByDay($entries, $rangeStart, $rangeEnd);

				}



			}

			 // Get coaches related to entries
			$coaches = DB::table('users')
				->selectRaw("id, IFNULL(NULLIF(CONCAT(first_name, ' ', last_name), ' '), name) as display_name")
				->where(function ($query) {
					$query->whereRaw("find_in_set(".config('racster.coaches_role').", replace(user_roles, '|', ',')) > 0");
					if (Auth::user()->hasRole('admin') and env('APP_ENV') == 'dev'){
						$query->orWhereIn('id', config('racster.superadmin'));
					}
				})
				->whereNull('deleted_at')
				->orderBy('display_name', 'asc')
				->pluck('display_name', 'id')
				->toArray();

			// Get user transactions balance
			$transactions_balance = UserTransactions::getBalance();

			return view('timetable.'.$this->viewform, [
				'assets' => $asset_list,
				'viewform' => $this->viewform,
				'viewtime' => (!empty($caltime) ? $caltime : ''),
				'entries' => (!empty($entries_list) ? $entries_list : []),
				'events' => (!empty($eventsForGrid) ? $eventsForGrid : []),
				'coaches' => (!empty($coaches) ? $coaches : ''),
				'transactions_balance' => (!empty($transactions_balance) ? $transactions_balance : 0),
			]);

		}

		return redirect(route('nouser'))
			->with('notice', trans('racster.no-rights-for-op'));

	}

	/**
	 * Get entry type default price
	 */
	public function getEntryTypePrice(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('client') and !empty($request->input('tid'))){

			// Get entry type price by asset id
			$type_price = DB::table('racster_assets')
				->where('type', 'entry-price')
				->where('parent', $request->input('tid'))
				->whereNull('deleted_at')
				->value('title');

			return response()->json([
				'success' => true,
				'price' => (!empty($type_price) ? $type_price : config('racster.default-price')),
				'msg' => trans('racster.entry-type-price-acquired-successfully'),
			]);

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Create recurring dates array between chosen dates
	 */
	public function createRecurringDates(Request $request)
	{

		// Only users with manager/admin role can manage entries
		if (request()->ajax() and Auth::user()->hasRole('manager') and !empty($request->input('data'))){

			$validator = Validator::make($request->input('data'), $this->recurring_rules); // Validate input rules

			if ($validator->passes()){

				// Define recurring dates arrays
				$recurring_dates = []; $dates = collect();

				// Define recurring dates starting and ending dates
				$start = CarbonImmutable::createFromFormat('d.m.Y', $request->input('data.recurring_start'))->startOfDay()->addDay();
				$end = CarbonImmutable::createFromFormat('d.m.Y', $request->input('data.recurring_period'))->endOfDay();

				// Create dates only with correct range
				if (!$start->gt($end)){

					// Define weekdays as ISO 1 (Mon) … 7 (Sun)
					$weekdays = array_map('intval', $request->input('data.recurring_day') ?? []);

					// Create dates only if weekdays are selected
					if (!empty($weekdays)){

						// Define time "HH:MM" for dates
						[$hour, $minute] = explode(':', $request->input('data.recurring_time') ?? '00:00');

						// Define step of dates (weekly vs over-week)
						$stepWeeks = $request->input('data.recurring_specs') === 'over-week' ? 2 : 1;

						// Helper: get the next or same weekday to use
						$nextOrSame = function ($date, int $isoWeekday) {
							$dow = $date->dayOfWeekIso;
							$delta = ($isoWeekday - $dow + 7) % 7;
							return $date->copy()->addDays($delta);
						};

						// Include starting date if it is in selected weekdays
						if (in_array($start->dayOfWeekIso, $weekdays, true)) {
							$dates->push($start->setTime($hour, $minute));
						}

						// Define anchor week to start date selection
						$anchorWeekStart = $start->startOfWeek(CarbonInterface::MONDAY);

						// Create a week-by-week (or bi-weekly) period from the anchor week
						$weekPeriod = CarbonPeriod::create($anchorWeekStart, "{$stepWeeks} weeks", $end);

						// Add the selected weekdays that fall within that (bi-)week period
						foreach ($weekPeriod as $weekStart) {
							foreach ($weekdays as $weekday) {
								$candidate = $nextOrSame($weekStart, $weekday)->setTime($hour, $minute);
								if ($candidate->betweenIncluded($start, $end)) {
									$dates->push($candidate);
								}
							}
						}

						// Cleanup, sort and format dates
						$dates = $dates->unique(fn ($d) => $d->toDateTimeString())->sort()->values();

						// Add dates to recurring dates array
						$recurring_dates = $dates->map(fn ($d) => $d->format('d.m.Y H:i'))->all();

					}

				}

				return response()->json([
					'success' => true,
					'dates' => $recurring_dates,
					'msg' => trans('racster.recurring-dates-created-successfully'),
				]);

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Acquire clients list to add to entry
	 */
	public function acquireClientsList(Request $request)
	{

		// Only users with manager/admin role can manage entries
		if (request()->ajax() and Auth::user()->hasRole('manager') and !empty($request->input('kword'))){

			 // Get clients array
			$users = DB::table('users')
				->selectRaw("id, IFNULL(NULLIF(CONCAT(first_name, ' ', last_name), ' '), name) as display_name")
				->whereRaw("IFNULL(NULLIF(CONCAT(first_name, ' ', last_name), ' '), name) LIKE '%".$request->input('kword')."%'")
				->whereNotNull('email_verified_at')
				->whereNull('deleted_at')
				->orderBy('display_name', 'asc')
				->pluck('display_name', 'id')
				->toArray();

			return response()->json([
				'success' => true,
				'clients' => (!empty($users) ? $users : []),
				'msg' => trans('racster.entry-info-acquired-successfully'),
			]);

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Acquire timetable recurring entry past dates
	 */
	public function acquireRecurringEntryPastDates(Request $request)
	{

		// Only users with manager/admin role can manage entries
		if (request()->ajax() and Auth::user()->hasRole('manager') and !empty($request->input('eid'))){

			// Get entry past dates
			$entry_dates = DB::table('racster_entry_dates')
				->select('*', DB::raw("DATE_FORMAT(entry_start, '%d.%m.%Y %H:%i') as starting_date"))
				->where('entry_id', $request->input('eid'))
				->where('entry_start', '<', Carbon::now()->startOfDay())
				->whereNull('deleted_at')
				->orderBy('entry_start', 'DESC')
				->get();

			if (!empty($entry_dates) and count($entry_dates) > 0){

				return response()->json([
					'success' => true,
					'dates' => $entry_dates,
					'msg' => trans('racster.entry-past-dates-acquired-successfully'),
				]);

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Acquire timetable recurring entry subscriptions
	 */
	public function acquireRecurringEntrySubscriptions(Request $request)
	{

		// Only users with manager/admin role can manage entries
		if (request()->ajax() and Auth::user()->hasRole('manager') and !empty($request->input('eid'))){

			// Get entry subscriptions
			$subscriptions = UserPayment::select('racster_user_payments.*', DB::raw("DATE_FORMAT(subscriptions.ends_at, '%d.%m.%Y') as ending_date"), 'subscriptions.id as subid')
				->selectRaw("IFNULL(NULLIF(CONCAT(users.first_name, ' ', users.last_name), ' '), users.name) as display_name")
				->leftJoin('users', function($join){
					$join->on('users.id', '=', 'racster_user_payments.user_id');
				})
				->leftJoin('subscriptions', function($join){
					$join->on('subscriptions.stripe_id', '=', 'racster_user_payments.stripe_subscription_id');
				})
				->where('racster_user_payments.entry_id', $request->input('eid'))
				->whereNull('racster_user_payments.date_id')
				->whereNotNull('racster_user_payments.stripe_invoice_id')
				->subscriptions()
				->where('racster_user_payments.status', 'paid')
				->whereNull('racster_user_payments.deleted_at')
				->get();

			if (!empty($subscriptions) and count($subscriptions) > 0){

				return response()->json([
					'success' => true,
					'subs' => $subscriptions,
					'msg' => trans('racster.entry-subscriptions-acquired-successfully'),
				]);

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op'),
		]);

	}

	/**
	 * Manage timetable entry data
	 */
	public function manageEntryData(Request $request, $eid = null)
	{

		// Only users with manager/admin role can manage entries
		if (Auth::user()->hasRole('manager')){

			// Define base "now" time rounded to closest step
			$basenow = ceil(time() / (config('racster.timetable-range.step') * 60)) * (config('racster.timetable-range.step') * 60);

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

			 // Get coaches related to entries
			$coaches = DB::table('users')
				->selectRaw("id, IFNULL(NULLIF(CONCAT(first_name, ' ', last_name), ' '), name) as display_name")
				->where(function ($query) {
					$query->whereRaw("find_in_set(".config('racster.coaches_role').", replace(user_roles, '|', ',')) > 0");
					if (Auth::user()->hasRole('admin') and !empty(config('racster.superadmin')) and in_array(Auth::user()->id, config('racster.superadmin')) and env('APP_ENV') == 'dev'){
						$query->orWhereIn('id', config('racster.superadmin'));
					}
				})
				->whereNull('deleted_at')
				->orderBy('display_name', 'asc')
				->pluck('display_name', 'id')
				->toArray();

			if (!empty($eid)){

				// Get entry main data
				$entry = DB::table('racster_entries')
					->where('id', $eid)
					->whereNull('deleted_at')
					->first();

				// Define entry dates query with main conditions
				$entry_dates_query = DB::table('racster_entry_dates')
					->where('entry_id', $eid)
					->whereNull('deleted_at');

				// With recurring dates entry remove older dates from management
				if (!empty($entry->recurring_entry) and $entry->recurring_entry == 1){
					$entry_dates_query->where('entry_start', '>=', Carbon::now()->startOfDay());
				}

				// Get entry dates data
				$entry_dates = $entry_dates_query->get();

				if (!empty($entry_dates)){

					// Add entry dates to entry data
					foreach ($entry_dates as $date){

						$entry->entry_start[] = $date->entry_start;
						$entry->entry_ending[] = $date->entry_ending;
						$entry->entry_length[] = $date->entry_length;
						$entry->entry_location[] = $date->entry_location;
						$entry->private_entry[] = $date->private_entry;
						$entry->date_id[] = $date->id;

						// Define entry client limit
						if (!property_exists($entry, 'client_limit')){
							$entry->client_limit = ((!empty($date->client_limit) and $date->client_limit > 0) ? $date->client_limit : 0);
						}

						// Define entry price
						if (!property_exists($entry, 'entry_price')){
							$entry->entry_price = ((!empty($date->entry_price) and $date->entry_price > 0) ? round($date->entry_price, 0) : config('racster.default-price'));
						}

						// Define entry extra price
						if (!property_exists($entry, 'extra_price')){
							$entry->extra_price = ((!empty($date->extra_price) and $date->extra_price == 1) ? $date->extra_price : '');
						}

						// Add entry date clients to entry data
						$entry->clients[] = DB::table('racster_entry_users')
							->selectRaw("users.id, IFNULL(NULLIF(CONCAT(users.first_name, ' ', users.last_name), ' '), users.name) as display_name, racster_entry_users.paying, racster_entry_users.user_quantity as cqty")
							->leftJoin('users', function($join){
								$join->on('users.id', '=', 'racster_entry_users.user_id');
							})
							->where('racster_entry_users.entry_id', $eid)
							->where('racster_entry_users.date_id', $date->id)
							->where('racster_entry_users.user_type', 'client')
							->whereNull('racster_entry_users.deleted_at')
							->orderBy('display_name', 'asc')
							->get();

					}

				}

				// Check if there are past dates and subscriptions with recurring entry
				if (!empty($entry->recurring_entry) and $entry->recurring_entry == 1){

					$past_dates = DB::table('racster_entry_dates')
						->where('entry_id', $eid)
						->whereNull('deleted_at')
						->count();

					$subscriptions = UserPayment::where('entry_id', $entry->id)
						->whereNotNull('stripe_invoice_id')
						->subscriptions()
						->where('status', 'paid')
						->whereNull('deleted_at')
						->count();

				}

				// Define entry users list array
				$users_list = [];

				// Get users related to entry
				$entry_users = DB::table('racster_entry_users')
					->select('racster_entry_users.*')
					->selectRaw("IFNULL(NULLIF(CONCAT(users.first_name, ' ', users.last_name), ' '), users.name) as display_name")
					->leftJoin('users', function($join){
						$join->on('users.id', '=', 'racster_entry_users.user_id');
					})
					->where('racster_entry_users.entry_id', $eid)
					->whereIn('racster_entry_users.user_type', ['client', 'coach'])
					->whereNull('racster_entry_users.deleted_at')
					->get();

				foreach ($entry_users as $user){
					$users_list[$user->user_type][$user->user_id] = $user;
				}

			}

			return view('timetable.manageEntry', [
				'entry_id' => $eid,
				'basenow' => $basenow,
				'assets' => $asset_list,
				'coaches' => (!empty($coaches) ? $coaches : ''),
				'entry_data' => (!empty($entry) ? $entry : ''),
				'users' => (!empty($users_list) ? $users_list : []),
				'past_dates' => (!empty($past_dates) ? $past_dates : 0),
				'subscriptions' => (!empty($subscriptions) ? $subscriptions : 0),
			]);

		}

		return Redirect::to(LaravelLocalization::localizeUrl('/home'))
			->with('notice', trans('racster.no-rights-for-op'));

	}

	/**
	 * Update timetable entry data
	 */
	public function updateEntryData(Request $request, $eid = null)
	{

		// Only users with manager/admin role can manage entries
		if (Auth::user()->hasRole('manager') and $request->isMethod('post')){

			// Define entry rules array
			$entry_rules = $this->entry_rules;

			if (!empty($request->input('entry_type'))){

				// Get entry lengths count by entry type
				$type_lengths = DB::table('racster_assets')
					->where('type', 'entry-length')
					->where('parent', $request->input('entry_type'))
					->whereNull('deleted_at')
					->orderByRaw("CAST(extra AS UNSIGNED)")
					->count();

				// Remove not required array elements validation
				unset($entry_rules[($type_lengths > 0 ? 'entry_ending.*' : 'entry_length.*')]);

			}

			$validator = Validator::make($request->all(), $entry_rules); // Validate input rules

			if ($validator->fails()) { // If main validation fails

				// Get clients list for new clients
				if (!empty($request->input('entry_clients'))){

					// Define clients list array
					$clients_list = [];

					foreach ($request->input('entry_clients') as $dkey => $client){

						 // Get clients array
						$clients_list[$dkey] = DB::table('users')
							->selectRaw("id, IFNULL(NULLIF(CONCAT(first_name, ' ', last_name), ' '), name) as display_name")
							->whereIn('id', $client)
							->whereNull('deleted_at')
							->orderBy('display_name', 'asc')
							->pluck('display_name', 'id')
							->toArray();

					}

					// Add new clients list to session
					Session::flash('clients-list', $clients_list);

				}

				return Redirect::to(LaravelLocalization::localizeUrl('/manage/entry'.(!empty($eid) ? '/'.$eid : '')))
							->with('notice', trans('racster.all-fields-are-required'))
							->withInput($request->all())
							->withErrors($validator);

			}

			// Get length assets
			$length_assets = DB::table('racster_assets')
				->where('type', 'entry-length')
				->whereNull('deleted_at')
				->orderByRaw("CAST(extra AS UNSIGNED)")
				->orderBy('title', 'asc')
				->pluck('title', 'id')
				->toArray();

			// Define entry main data
			$entry_data = [
				'creator_id'		=> Auth::user()->id,
				'entry_title'		=> (!empty($request->input('entry_title')) ? $request->input('entry_title') : NULL),
				'entry_description'	=> (!empty($request->input('entry_description')) ? $request->input('entry_description') : NULL),
				'entry_type'		=> (!empty($request->input('entry_type')) ? $request->input('entry_type') : NULL),
				'client_level'		=> (!empty($request->input('client_level')) ? $request->input('client_level') : NULL),
				'various_clients'	=> ((!empty($request->input('various_clients')) and !empty($request->input('various_clients')) and $request->input('various_clients') == 'Y') ? 1 : 0),
				'recurring_entry'	=> ((!empty($request->input('recurring_entry')) and !empty($request->input('recurring_entry')) and $request->input('recurring_entry') == 'Y') ? 1 : 0),
				'entry_monthly_fee'	=> ((!empty($request->input('entry_monthly')) and $request->input('entry_monthly') > 0) ? $request->input('entry_monthly') : NULL),
				'updated_at'		=> Carbon::now(),
			];

			if (empty($eid)){

				$entry_data['created_at'] = Carbon::now();

				// Add new entry main data
				$eid = DB::table('racster_entries')
					->insertGetId($entry_data);

			}else{

				// Define removed entry dates query and main conditions
				$removed_dates_query = DB::table('racster_entry_dates')
					->whereNotIn('id', (!empty($request->input('entry_lid')) ? array_filter($request->input('entry_lid')) : []))
					->where('entry_id', $eid)
					->whereNull('deleted_at');

				// With recurring dates exclude entry older dates from management
				if (!empty($entry_data['recurring_entry']) and $entry_data['recurring_entry'] == 1){
					$removed_dates_query->where('entry_start', '>=', Carbon::now()->startOfDay());
				}

				// Get removed entry dates
				$removed_dates = $removed_dates_query->pluck('id')->toArray();

				if (!empty($removed_dates)){

					// Mark old entry dates as deleted
					DB::table('racster_entry_dates')
						->whereIn('id', $removed_dates)
						->where('entry_id', $eid)
						->whereNull('deleted_at')
						->update([
							'updated_at'	=> Carbon::now(),
							'deleted_at'	=> Carbon::now(),
						]);

					// Mark old entry clients as deleted
					DB::table('racster_entry_users')
						->where('entry_id', $eid)
						->whereIn('date_id', $removed_dates)
						->where('user_type', 'client')
						->whereNull('deleted_at')
						->update([
							'updated_at'	=> Carbon::now(),
							'deleted_at'	=> Carbon::now(),
						]);

					// Mark old entry clients transactions as deleted
					UserTransactions::deleteTransactionsByDates($removed_dates, [/*'used', */'onhold']);

				}

				// Update existing entry main data
				DB::table('racster_entries')
					->where('id', $eid)
					->whereNull('deleted_at')
					->update($entry_data);

				// Get coaches related to entry
				$entry_coaches = DB::table('racster_entry_users')
					->where('entry_id', $eid)
					->where('user_type', 'coach')
					->whereNull('deleted_at')
					->pluck('id', 'user_id')
					->toArray();

			}

			// Define entry users array
			$entry_users = [];

			// Add new dates to entry
			foreach (array_keys($request->input('entry_start')) as $dcnt){

				// Create ending from starting date
				if (!empty($request->input('entry_length')) and !empty($request->input('entry_length.'.$dcnt))){
					if (!empty($length_assets) and array_key_exists($request->input('entry_length.'.$dcnt), $length_assets)){
						if (!empty($request->input('entry_start')) and !empty($request->input('entry_start.'.$dcnt))){
							$ending = strtotime(preg_replace("/(\d{2}).(\d{2}).(\d{4}) (\d{2}):(\d{2})/", "$2/$1/$3 $4:$5", $request->input('entry_start.'.$dcnt)))+($length_assets[$request->input('entry_length.'.$dcnt)]*60);
						}
					}
				}

				// Define date data array
				$date_data = [
					'entry_start'		=> ((!empty($request->input('entry_start')) and !empty($request->input('entry_start.'.$dcnt))) ?
						Carbon::createFromFormat('d.m.Y H:i', $request->input('entry_start.'.$dcnt)) : NULL),
					'entry_ending'		=> (!empty($ending) ? Carbon::createFromTimestamp($ending)->setTimezone(config('app.timezone')) :
						((!empty($request->input('entry_ending')) and !empty($request->input('entry_ending.'.$dcnt))) ?
							Carbon::createFromFormat('d.m.Y H:i', $request->input('entry_ending.'.$dcnt)) : NULL)),
					'entry_length'		=> ((!empty($request->input('entry_length')) and !empty($request->input('entry_length.'.$dcnt))) ? $request->input('entry_length.'.$dcnt) : NULL),
					'entry_location'	=> ((!empty($request->input('entry_location')) and !empty($request->input('entry_location.'.$dcnt))) ? $request->input('entry_location.'.$dcnt) : NULL),
					'private_entry'		=> ((!empty($request->input('private_entry')) and !empty($request->input('private_entry.'.$dcnt)) and $request->input('private_entry.'.$dcnt) == 'Y') ? 1 : 0),
					'client_limit'		=> ((!empty($request->input('client_limit')) and $request->input('client_limit') > 0) ? $request->input('client_limit') : NULL),
					'client_count'		=> ((!empty($request->input('entry_clients')) and !empty($request->input('entry_clients.'.$dcnt))) ? count($request->input('entry_clients.'.$dcnt)) : NULL),
					'entry_price'		=> ((!empty($request->input('entry_price')) and $request->input('entry_price') > 0) ? $request->input('entry_price') : config('racster.default-price')),
					'extra_price'		=> ((!empty($request->input('extra_price')) and !empty($request->input('extra_price')) and $request->input('extra_price') == 'Y') ? 1 : 0),
					'updated_at'		=> Carbon::now(),
				];

				if (!empty($request->input('entry_lid')) and !empty($request->input('entry_lid.'.$dcnt))){

					// Define date ID
					$date_id = $request->input('entry_lid.'.$dcnt);

					// Update existing date data fields
					DB::table('racster_entry_dates')
						->where('id', $date_id)
						->whereNull('deleted_at')
						->update($date_data);

					// Get clients related to entry date
					$date_clients = DB::table('racster_entry_users')
						->where('entry_id', $eid)
						->where('date_id', $date_id)
						->where('user_type', 'client')
						->whereNull('deleted_at')
						->pluck('id', 'user_id')
						->toArray();

				}else{

					// Add additional date data fields for new date
					$date_data['creator_id'] = Auth::user()->id;
					$date_data['entry_id'] = $eid;
					$date_data['created_at'] = Carbon::now();

					// Add new entry date to database
					$date_id = DB::table('racster_entry_dates')
						->insertGetId($date_data);

				}

				if (!empty($request->input('entry_clients')) and !empty($request->input('entry_clients.'.$dcnt))){

					foreach ($request->input('entry_clients.'.$dcnt) as $client){

						// Get client discount info
						$user_discount = DB::table('users')->where('id', $client)->whereNull('deleted_at')->value('discount_amount');

						// Define entry price
						$entry_price = ((!empty($request->input('entry_price')) and $request->input('entry_price') > 0) ? $request->input('entry_price') : 0);

						// Add discount if client has discount
						if (!empty($user_discount) and $user_discount > 0 and $entry_price > 0 and $request->input('extra_price') != 'Y'){
							$entry_price = $entry_price-round(($entry_price*$user_discount/100), 0);
						}

						// Define client quantity
						if (!empty($request->input('client_quantity')) and !empty($request->input('client_quantity.'.$dcnt)) and array_key_exists($client, $request->input('client_quantity.'.$dcnt)) and $request->input('client_quantity.'.$dcnt.'.'.$client) > 1){
							$client_quantity = $request->input('client_quantity.'.$dcnt.'.'.$client);
						}else{
							$client_quantity = 1;
						}

						// Multiply the entry price with user quantity
						$entry_price = $client_quantity*$entry_price;


						if (!empty($date_clients) and array_key_exists($client, $date_clients)){

							// Update entry clients data
							DB::table('racster_entry_users')
								->where('id', $date_clients[$client])
								->whereNull('deleted_at')
								->update([
									'paying'		=> ((!empty($request->input('entry_paying')) and !empty($request->input('entry_paying.'.$dcnt)) and in_array($client, $request->input('entry_paying.'.$dcnt))) ? 1 : 0),
									'updated_at'	=> Carbon::now(),
								]);

							// Check if client transaction exists
							$transaction = UserTransactions::getUserDateTransaction(['used', 'onhold'], $date_id, $client);

							if (!empty($request->input('entry_paying')) and !empty($request->input('entry_paying.'.$dcnt)) and in_array($client, $request->input('entry_paying.'.$dcnt))){

								if (!empty($transaction->id)){

									// Update client transaction
									UserTransactions::updateTransaction($transaction->id, [
										'transaction_amount'	=> $entry_price,
										'transaction_comment'	=> trans('racster.transaction-attending-comment'),
									]);

								}else{

									// Add new client transaction
									UserTransactions::createTransaction('onhold'/*($entry_data['recurring_entry'] == 1 ? 'onhold' : 'used')*/, [
										'amount'	=> $entry_price,
										'comment'	=> trans('racster.transaction-attending-comment'),
										'date_id'	=> $date_id,
										'user_id'	=> $client,
									]);

								}

							}elseif (!empty($transaction->id)){

								// Remove client transaction
								UserTransactions::deleteTransactionsByDates([$date_id], [/*'used', */'onhold'], [$client]);

							}

							// Reset transaction variable
							unset($transaction);

							// Remove client from date clients
							unset($date_clients[$client]);
							
						}else{

							// Add new client to entry users array
							$entry_users[] = [
								'creator_id'	=> Auth::user()->id,
								'entry_id'		=> $eid,
								'date_id'		=> $date_id,
								'user_type'		=> 'client',
								'user_id'		=> $client,
								'user_quantity'	=> $client_quantity,
								'paying'		=> ((!empty($request->input('entry_paying')) and !empty($request->input('entry_paying.'.$dcnt)) and in_array($client, $request->input('entry_paying.'.$dcnt))) ? 1 : 0),
								'created_at'	=> Carbon::now(),
								'updated_at'	=> Carbon::now(),
							];

							// Add client transaction
							if (!empty($request->input('entry_paying')) and !empty($request->input('entry_paying.'.$dcnt)) and in_array($client, $request->input('entry_paying.'.$dcnt))){

								// Add new client transaction
								UserTransactions::createTransaction('onhold'/*($entry_data['recurring_entry'] == 1 ? 'onhold' : 'used')*/, [
									'amount'	=> $entry_price,
									'comment'	=> trans('racster.transaction-attending-comment'),
									'date_id'	=> $date_id,
									'user_id'	=> $client,
								]);

							}

						}

						// Unset user discount variable
						unset($user_discount);

					}

				}

				// Mark client removed from entry as deleted
				if (!empty($date_clients)){

					// Remove client transactions for the date
					UserTransactions::deleteTransactionsByDates([$date_id], [/*'used', */'onhold'], array_keys($date_clients));

					// Remove clients from entry
					DB::table('racster_entry_users')
						->whereIn('id', $date_clients)
						->whereNull('deleted_at')
						->update([
							'updated_at'	=> Carbon::now(),
							'deleted_at'	=> Carbon::now(),
						]);

				}

				// Reset ending time and client variable
				unset($ending); unset($date_clients);

			}

			// Add new coaches to entry users
			foreach ($request->input('entry_coaches') as $coach){

				// Add only new coaches
				if (!empty($entry_coaches) and array_key_exists($coach, $entry_coaches)){

					unset($entry_coaches[$coach]);

				}else{

					// Add new coach to entry users array
					$entry_users[] = [
						'creator_id'	=> Auth::user()->id,
						'entry_id'		=> $eid,
						'date_id'		=> NULL,
						'user_type'		=> 'coach',
						'user_id'		=> $coach,
						'user_quantity'	=> 1,
						'paying'		=> 0,
						'created_at'	=> Carbon::now(),
						'updated_at'	=> Carbon::now(),
					];

				}

			}

			if (!empty($entry_users)){

				// Add new entry users to database
				DB::table('racster_entry_users')
					->insert($entry_users);

			}

			if (!empty($entry_coaches)){

				// Mark coaches removed from entry as deleted
				DB::table('racster_entry_users')
					->whereIn('id', $entry_coaches)
					->whereNull('deleted_at')
					->update([
						'updated_at'	=> Carbon::now(),
						'deleted_at'	=> Carbon::now(),
					]);

			}

			// Get client count for each entry date
			$entry_dates = DB::table('racster_entry_dates as date')
				->select('date.id', DB::raw('SUM(user.user_quantity) as total_quantity'))
				->leftJoin('racster_entry_users as user', function($join) {
					$join->on('date.entry_id', '=', 'user.entry_id');
					$join->on('date.id', '=', 'user.date_id');
					$join->where('user.user_type', 'client');
					$join->whereNull('user.deleted_at');
				})
				->where('date.entry_id', $eid)
				->whereNull('date.deleted_at')
				->groupBy('date.id')
				->pluck('total_quantity', 'date.id')
				->toArray();

			if (!empty($entry_dates)){

				foreach ($entry_dates as $entry_date => $quantity){

					// Update client count in for entry date
					DB::table('racster_entry_dates')
						->where('id', $entry_date)
						->whereNull('deleted_at')
						->update([
							'client_count' => ((empty($quantity) or $quantity < 0) ? 0 : $quantity),
							'updated_at' => Carbon::now(),
						]);
				}
			}


			// Update all users active subscriptions end date
			if ($entry_data['recurring_entry'] == 1){

				$subscriptions = UserPayment::where('entry_id', $eid)
					->whereNotNull('stripe_invoice_id')
					->subscriptions()
					->where('status', 'paid')
					->whereNull('deleted_at')
					->pluck('user_id', 'stripe_subscription_id');

				if (!empty($subscriptions)){

					foreach ($subscriptions as $subID => $userID){

						// Check if subscription is active
						$sub = CashierSubscription::where('stripe_id', $subID)->where('stripe_status', 'active')->first();

						if ($sub){

							// Get the entry furthest ending time of user dates
							$last_date = DB::table('racster_entry_dates as date')
								->select('date.*')
								->leftJoin('racster_entry_users as user', function($join) use ($userID) {
									$join->on('date.entry_id', '=', 'user.entry_id');
									$join->on('date.id', '=', 'user.date_id');
									$join->where('user.user_type', 'client');
									$join->where('user.user_id', $userID);
									$join->where('user.paying', 1);
									$join->whereNull('user.deleted_at');
								})
								->where('date.entry_id', $eid)
								->whereNull('date.deleted_at')
								->max('date.entry_ending');

							// Add furthest entry date as subscription end date
							app(SubscriptionEndService::class)->setEndByStripeId($subID, (!empty($last_date) ? Carbon::parse($last_date)->addDays(1) : null));

						}

					}

				}

			}

			return Redirect::to(LaravelLocalization::localizeUrl('/manage/entry'.(!empty($eid) ? '/'.$eid : '')))
				->with('message', trans('racster.entry-data-successfully-updated'));

		}

		return Redirect::to(LaravelLocalization::localizeUrl('/home'))
			->with('notice', trans('racster.no-rights-for-op'));

	}

	/**
	 * Get user next entry info
	 */
	public static function getUserNextEntry()
	{

		// Get user next entry info
		$entry_info = DB::table('racster_entry_users as user')
			->select('entry.*', 'date.entry_start', 'date.entry_ending', 'date.entry_length', 'date.entry_location', 'user.user_type', 'user.paying')
			->join('racster_entry_dates as date', function ($join) {
				$join->on('date.id', '=', 'user.date_id');
				$join->on('date.entry_id', '=', 'user.entry_id');
				$join->whereNull('date.deleted_at');
			})
			->join('racster_entries as entry', function($join){
				$join->on('entry.id', '=', 'user.entry_id');
				$join->whereNull('entry.deleted_at');
			})
			->where('date.entry_start', '>=', Carbon::now())
			->where('user.user_id', Auth::user()->id)
			->whereNull('user.deleted_at')
			->orderBy('entry_start', 'ASC')
			->orderBy('entry_ending', 'ASC')
			->first();

		return $entry_info;

	}

}
