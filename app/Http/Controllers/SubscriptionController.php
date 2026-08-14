<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Response;
use LaravelLocalization;
use Log;

use Carbon\Carbon;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\UserPayment;
use App\Services\SubscriptionEndService;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Laravel\Cashier\Subscription as CashierSubscription;

class SubscriptionController extends Controller
{

	/**
	 * Check if user is subscription owner
	 */
	protected function ensureOwner(CashierSubscription $subscription): bool
	{
		$u = auth()->user();

		if (! $u) {
			return false;
		}

		return (int) $subscription->user_id === (int) $u->id;

	}

	/**
	 * Format money
	 */
	protected function formatMoney(?int $amount, ?string $currency): string
	{
		
		if ($amount === null) return '—';

		return number_format($amount / 100, 2).' '.strtoupper($currency ?? config('racster.main-currency'));

	}

	/**
	 * Check if subscription exists in stripe
	 */
	protected function safeStripeSubscription(CashierSubscription $subscription): ?object
	{
		try {
			return $subscription->asStripeSubscription(); // returns \Stripe\Subscription
		} catch (\Throwable $e) {
			Log::channel('stripepayments')->info('Stripe subscription fetch failed', [
				'local_subscription_id'	=> $subscription->id,
				'stripe_id'				=> $subscription->stripe_id,
				'message'				=> $e->getMessage(),
			]);
			return null;
		}
	}

	/**
	 * Build a Blade-ready payload for a given Cashier Subscription model.
	 */
	protected function buildPayloadFromModel(CashierSubscription $subscription, ?UserPayment $payment = null): array
	{

		$endsAt = $subscription->ends_at;
		$status = $subscription->stripe_status;
		$onGrace = method_exists($subscription, 'onGracePeriod')
			? $subscription->onGracePeriod()
			: (optional($endsAt)?->isFuture() ?? false);

		$isCanceled = ($status === 'canceled') or !is_null($endsAt);

		$data = [
			'id'			=> $subscription->id,
			'name'			=> $subscription->name,
			'stripe_id'		=> $subscription->stripe_id,
			'stripe_status'	=> $status,
			'on_grace'		=> $onGrace,
			'ends_at'		=> optional($endsAt)?->toDateTimeString(),
			'canceled'		=> $isCanceled,
			'trial_ends_at'	=> optional($subscription->trial_ends_at)?->toDateTimeString(),
			'items'			=> $subscription->items()->get(['stripe_price','stripe_product','quantity']),
		];

		// Current period end from Stripe if possible
		if ($stripeSub = $this->safeStripeSubscription($subscription)) {
			$data['current_period_end'] = $stripeSub->current_period_end
				? Carbon::createFromTimestamp($stripeSub->current_period_end)->toDateTimeString()
				: null;
		}

		// Fallback payment if caller did not pass one
		$fallbackPayment = null;
		if (!$payment) {
			$fallbackPayment = UserPayment::where('stripe_subscription_id', $subscription->stripe_id)
				->latest('id')
				->first();
			$payment = $fallbackPayment;
		}

		// Fallback current period end from payment row
		if (empty($data['current_period_end']) and $payment and !empty($payment->period_end)) {
			$data['current_period_end'] = $payment->period_end;
		}

		// Add paused data
		if (is_null($subscription->is_paused) and ($stripeSub = $this->safeStripeSubscription($subscription))) {
			$paused = !empty($stripeSub->pause_collection);
			$data['paused'] = $paused;
			$data['pause_resumes_at'] = ($paused and !empty($stripeSub->pause_collection->resumes_at))
				? Carbon::createFromTimestamp($stripeSub->pause_collection->resumes_at)->toDateTimeString()
				: null;
		} else {
			$data['paused'] = (bool) $subscription->is_paused;
			$data['pause_resumes_at'] = $subscription->pause_resumes_at
				? Carbon::parse($subscription->pause_resumes_at)->toDateTimeString()
				: null;
		}

		$metadata = is_array($payment?->metadata) ? $payment->metadata : [];

		$priceIds = $data['items']->pluck('stripe_price')->filter()->values()->all();
		$localPrices = ProductPrice::with('product')
			->whereIn('stripe_price_id', $priceIds)
			->get()
			->keyBy('stripe_price_id');

		$data['items'] = $data['items']->map(function ($it) use ($localPrices, $metadata) {
			$lp = $localPrices->get($it->stripe_price);

			$baseAmount = !is_null($lp?->unit_amount) ? (int) $lp->unit_amount : null;
			$qty = max(1, (int) ($it->quantity ?? 1));

			$discountAmount = 0; // cents
			$discountPercent = 0; // percent

			if (!is_null($baseAmount)) {
				$rawAmount = data_get($metadata, 'user_discount_amount');
				$rawPercent = data_get($metadata, 'user_discount_percent');
				$rawEuros = data_get($metadata, 'user_discount_euros');
				$couponId = (string) data_get($metadata, 'stripe_coupon_id', '');

				// NEW FORMAT:
				// user_discount_amount = cents discount
				// user_discount_percent = informational percent
				if (is_numeric($rawPercent) and (int) $rawPercent > 0) {
					$discountPercent = max(0, min(100, (int) $rawPercent));
				}

				if (is_numeric($rawAmount) and (int) $rawAmount > 0) {
					$rawAmountInt = (int) $rawAmount;

					// OLD FORMAT:
					// user_discount_amount held the percent, eg "10"
					// usually paired with coupon id like "..._10_percent_forever"
					$isLegacyPercent =
						$discountPercent === 0 &&
						$rawAmountInt > 0 &&
						$rawAmountInt <= 100 &&
						(
							str_contains($couponId, '_percent_')
							|| (!is_numeric($rawEuros) and $rawAmountInt < 1000)
						);

					if ($isLegacyPercent) {
						$discountPercent = $rawAmountInt;
						$discountAmount = (int) round(($baseAmount * $discountPercent) / 100, 0);
					} else {
						$discountAmount = $rawAmountInt;
					}
				} elseif (is_numeric($rawEuros) and (int) $rawEuros > 0) {
					// Safety fallback if only euros exist
					$discountAmount = ((int) $rawEuros) * 100;
				} elseif ($discountPercent > 0) {
					// Safety fallback if only percent exists
					$discountAmount = (int) round(($baseAmount * $discountPercent) / 100, 0);
				}

				$discountAmount = max(0, min($discountAmount, $baseAmount));
			}

			$effectiveAmount = !is_null($baseAmount)
				? max(0, $baseAmount - $discountAmount)
				: null;

			return [
				'stripe_price'			=> $it->stripe_price,
				'quantity'				=> $qty,
				'product_name'			=> $lp?->product?->name ?? $it->stripe_product,
				'unit_amount'			=> $baseAmount,
				'effective_unit_amount'	=> $effectiveAmount,
				'discount_amount'		=> $discountAmount,
				'discount_percent'		=> $discountPercent,
				'currency'				=> strtoupper($lp?->currency ?? config('racster.main-currency')),
				'interval'				=> $lp?->interval,
			];

		})->all();

		return $data;

	}

	/**
	 * Get available prices for subscription
	 */
	protected function availablePricesForUI()
	{

		$prices = ProductPrice::with(['product' => fn($q) => $q->select('id','name','stripe_product_id','active')])
			->where('active', true)
			->whereNotNull('interval')
			->orderBy('product_id')
			->orderBy('unit_amount')
			->get(['id','stripe_price_id','product_id','unit_amount','currency','interval']);

		return $prices->map(fn ($p) => [
			'stripe_price' => $p->stripe_price_id,
			'label' => trim(sprintf(
				'%s %s/%s',
				$p->product?->name ?? '',
				$this->formatMoney($p->unit_amount, $p->currency),
				$p->interval ?? 'period'
			)),
		]);

	}

	/**
	 * Show user subscriptions
	 */
	public function listSelf(Request $request)
	{

		$user = $request->user();
		$subs = $user->subscriptions()->latest()->get(['id','stripe_id','stripe_status','ends_at','trial_ends_at']);

		return view('subscriptions.index', [
			'owner' => $user,
			'subscriptions' => $subs,
		]);

	}

	/**
	 * Show user subscription
	 */
	public function showSelf(Request $request, CashierSubscription $subscription)
	{

		if ($this->ensureOwner($subscription)){

			$owner = $request->user();
			$payment = null;
			$entry_data = null;

			$payment = UserPayment::where('stripe_subscription_id', $subscription->stripe_id)
				->where('user_id', $owner->id)
				->latest('id')
				->first();

			if (!empty($payment)){

				$entry_data = DB::table('racster_entry_users as user')
					->select('entry.entry_title')
					->selectRaw('MIN(date.entry_start) AS minDate, MAX(date.entry_start) AS maxDate')
					->join('racster_entry_dates as date', function ($join) {
						$join->on('date.id', '=', 'user.date_id');
						$join->on('date.entry_id', '=', 'user.entry_id');
						$join->whereNull('date.deleted_at');
					})
					->join('racster_entries as entry', function($join){
						$join->on('entry.id', '=', 'user.entry_id');
						$join->whereNull('entry.deleted_at');
					})
					->where('user.entry_id', $payment->entry_id)
					->where('user.user_type', 'client')
					->where('user.user_id', $owner->id)
					->where('user.paying', 1)
					->whereNull('user.deleted_at')
					->groupBy('entry.entry_title')
					->first();

			}

			return view('subscriptions.show', [
				'subscription'		=> $this->buildPayloadFromModel($subscription, $payment),
				'availablePrices'	=> $this->availablePricesForUI(),
				'owner'				=> $owner,
				'payment'			=> $payment,
				'entry'				=> (!empty($entry_data) ? $entry_data : ''),
				'has_discount'		=> (!empty(data_get($payment?->metadata, 'stripe_coupon_id')) or !empty(data_get($payment?->metadata, 'user_discount_amount'))),
			]);

		}else{

			return Redirect::to(LaravelLocalization::localizeUrl('/subscriptions'))
				->with('notice', trans('racster.no-rights-for-op'));

		}

	}

	/**
	 * Subscription will end at period end
	 */
/*	public function cancelSelf(Request $request, CashierSubscription $subscription)
	{

		$this->ensureOwner($subscription);
		$subscription->cancel();

		return back()->with('message', trans('stripe-products.subscription-will-end-at-period-end'));

	}*/

	/**
	 * Cancel subscription immediately for user
	 */
/*	public function cancelNowSelf(Request $request, CashierSubscription $subscription)
	{

		$this->ensureOwner($subscription);
		$subscription->cancelNow();

		return back()->with('message', trans('stripe-products.subscription-cancelled'));

	}*/

	/**
	 * Resume subscription for user
	 */
/*	public function resumeSelf(Request $request, CashierSubscription $subscription)
	{

		$this->ensureOwner($subscription);
		$subscription->resume();

		return back()->with('message', trans('stripe-products.subscription-resumed'));
	
	}*/

	/**
	 * Update subscription price for user
	 */
/*	public function swapSelf(Request $request, CashierSubscription $subscription)
	{

		$this->ensureOwner($subscription);
		$request->validate(['price_id' => ['required','string']]);
		$subscription->noProrate()->swap($request->price_id);

		return back()->with('message', trans('stripe-products.subscription-price-changed'));

	}*/

	/**
	 * Update subscription quantity for user
	 */
/*	public function updateQuantitySelf(Request $request, CashierSubscription $subscription)
	{

		$this->ensureOwner($subscription);
		$request->validate(['quantity' => ['required','integer','min:1','max:100000']]);
		$subscription->noProrate()->updateQuantity($request->integer('quantity'));

		return back()->with('message', trans('stripe-products.subscription-quantity-changed'));

	}*/

	/**
	 * Admin: show users with subscription list
	 */
	public function adminIndex(Request $request)
	{

		if (Auth::user()->hasRole('admin')){

			$users = User::whereNotNull('stripe_id')->whereHas('subscriptions')->orderBy('id','desc')->paginate(25);

			return view('subscriptions.admin', ['users' => $users]);

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: show user subscriptions
	 */
	public function listForUser(Request $request, User $user)
	{

		if (Auth::user()->hasRole('admin')){

			$subs = $user->subscriptions()->latest()->get(['id','stripe_id','stripe_status','ends_at','trial_ends_at']);

			return view('subscriptions.index', [
				'owner'			=> $user,
				'subscriptions'	=> $subs,
				'adminview'		=> true,
			]);

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: show user subscription
	 */
	public function showAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$owner = $subscription->user;
			$payment = null;
			$entry_data = null;

			$payment = UserPayment::where('stripe_subscription_id', $subscription->stripe_id)
				->where('user_id', $owner->id)
				->latest('id')
				->first();

			if (!empty($payment)){

				$entry_data = DB::table('racster_entry_users as user')
					->select('entry.entry_title')
					->selectRaw('MIN(date.entry_start) AS minDate, MAX(date.entry_start) AS maxDate')
					->join('racster_entry_dates as date', function ($join) {
						$join->on('date.id', '=', 'user.date_id');
						$join->on('date.entry_id', '=', 'user.entry_id');
						$join->whereNull('date.deleted_at');
					})
					->join('racster_entries as entry', function($join){
						$join->on('entry.id', '=', 'user.entry_id');
						$join->whereNull('entry.deleted_at');
					})
					->where('user.entry_id', $payment->entry_id)
					->where('user.user_type', 'client')
					->where('user.user_id', $owner->id)
					->where('user.paying', 1)
					->whereNull('user.deleted_at')
					->groupBy('entry.entry_title')
					->first();

			}

			return view('subscriptions.show', [
				'subscription'		=> $this->buildPayloadFromModel($subscription, $payment),
				'availablePrices'	=> $this->availablePricesForUI(),
				'owner'				=> $owner,
				'payment'			=> $payment,
				'entry'				=> (!empty($entry_data) ? $entry_data : ''),
				'has_discount'		=> (!empty(data_get($payment?->metadata, 'stripe_coupon_id')) or !empty(data_get($payment?->metadata, 'user_discount_amount'))),
				'adminview'			=> true,
			]);

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: cancel subscription at end of period
	 */
	public function cancelAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$subscription->cancel();
			return back()->with('message', trans('stripe-products.subscription-will-end-at-period-end-for-email', ['email' => $subscription->user->email]));

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: cancel subscription immediately
	 */
	public function cancelNowAdmin(
		Request $request,
		CashierSubscription $subscription,
		SubscriptionEndService $subscriptionEndService
	)
	{

		if (Auth::user()->hasRole('admin')){

			try {

				$subscriptionEndService->cancelNowWithoutProration(
					$subscription->stripe_id
				);

				return back()->with('message', trans(
					'stripe-products.subscription-cancelled-for-email',
					['email' => $subscription->user->email]
				));

			} catch (\Throwable $e) {

				Log::channel('stripepayments')->error('Subscription immediate cancel failed', [
					'user_id'			=> $subscription->user_id,
					'subscription_id'	=> $subscription->stripe_id,
					'message'			=> $e->getMessage(),
				]);

				return back()->with('notice', trans(
					'stripe-products.subscription-immediate-cancel-failed',
					['email' => $subscription->user->email]
				));

			}

		}else{

			return view('nouser');

		}

	}

	/**
	 * Admin: resume subscription
	 */
	public function resumeAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$subscription->resume();
			return back()->with('message', trans('stripe-products.subscription-resumed-for-email', ['email' => $subscription->user->email]));

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: update subscription price
	 */
	public function swapAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$request->validate(['price_id' => ['required','string']]);
			$subscription->noProrate()->swap($request->price_id);

			return back()->with('message', trans('stripe-products.subscription-price-changed-for-email', ['email' => $subscription->user->email]));

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: change subscription quantity
	 */
	public function updateQuantityAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$request->validate(['quantity' => ['required','integer','min:1','max:100000']]);
			$subscription->noProrate()->updateQuantity($request->integer('quantity'));

			return back()->with('message', trans('stripe-products.subscription-quantity-changed-for-email', ['email' => $subscription->user->email]));

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: pause subscription until a chosen date or +1 month
	 */
	public function pauseAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$request->validate([
				'resume_date' => ['nullable', 'date_format:d.m.Y', 'after_or_equal:today'],
				'behavior' => ['nullable', 'in:void,keep_as_draft,mark_uncollectible'],
			]);

			$behavior = $request->input('behavior', 'void');

			// Pause until date or +1 month
			if ($request->filled('resume_date')) {
				$resumeAt = Carbon::createFromFormat('d.m.Y', $request->resume_date)->setTimezone(config('app.timezone'))->endOfDay()->timestamp;
			} else {
				$resumeAt = Carbon::now()->setTimezone(config('app.timezone'))->addMonth()->timestamp;
			}

			$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

			$stripe->subscriptions->update(
				$subscription->stripe_id,
				[
					'pause_collection' => [
						'behavior' => $behavior,
						'resumes_at' => $resumeAt,
					],
				]
			);

			return back()->with('message', trans('stripe-products.subscription-paused-for-email', ['email' => $subscription->user->email, 'date' => Carbon::createFromTimestamp($resumeAt)->setTimezone(config('app.timezone'))->toDateTimeString()]));

		}else{ return view('nouser'); }

	}

	/**
	 * Admin: resume subscription immediately from pause
	 */
	public function restartAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
			$stripe->subscriptions->update(
				$subscription->stripe_id,
				[
					'pause_collection' => null,
				]
			);

			return back()->with('message', trans('stripe-products.subscription-resumed-for-email', ['email' => $subscription->user->email]));

		}else{ return view('nouser'); }

	}

	public function updateTrialAdmin(Request $request, CashierSubscription $subscription)
	{

		if (Auth::user()->hasRole('admin')){

			$request->validate([
				'trial_end_date' => ['required', 'date_format:d.m.Y', 'after_or_equal:today'],
			]);

			$tz = config('app.timezone');
			$selectedDate = Carbon::createFromFormat('d.m.Y', $request->trial_end_date, $tz);

			if ($selectedDate->isToday()) {
				$newTrialEnd = Carbon::now($tz)->addMinutes(5);
			} else {
				$newTrialEnd = $selectedDate->startOfDay();
			}

			$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

			// Update Stripe first
			$stripe->subscriptions->update(
				$subscription->stripe_id,
				[
					'trial_end' => $newTrialEnd->timestamp,
				]
			);

			// Retrieve immediately so UI reflects Stripe truth now
			$stripeSub = $stripe->subscriptions->retrieve($subscription->stripe_id, []);

			$trialEndsAt = !empty($stripeSub->trial_end)
				? Carbon::createFromTimestamp($stripeSub->trial_end)->setTimezone(config('app.timezone'))
				: null;

			$currentPeriodEnd = !empty($stripeSub->current_period_end)
				? Carbon::createFromTimestamp($stripeSub->current_period_end)->setTimezone(config('app.timezone'))
				: null;

			// Sync local Cashier row
			$subscription->forceFill([
				'trial_ends_at' => $trialEndsAt,
				'stripe_status' => $stripeSub->status ?? $subscription->stripe_status,
			])->save();

			return back()->with('message', trans('stripe-products.subscription-trial-changed-for-email', ['email' => $subscription->user->email]));

		}else{ return view('nouser'); }

	}

	/**
	 * Remove discount coupon from subscription for user
	 */
/*	public function removeDiscountSelf(Request $request, CashierSubscription $subscription)
	{

		if (!$this->ensureOwner($subscription)) {
			return Redirect::to(LaravelLocalization::localizeUrl('/subscriptions'))
				->with('notice', trans('racster.no-rights-for-op'));
		}

		return $this->removeDiscountFromSubscription($subscription, false);

	}*/

	/**
	 * Remove discount coupon from subscription for admin
	 */
	public function removeDiscountAdmin(Request $request, CashierSubscription $subscription)
	{

		if (!Auth::user()->hasRole('admin')) {
			return view('nouser');
		}

		return $this->removeDiscountFromSubscription($subscription, true);

	}

	/**
	 * Shared discount removal logic
	 */
	protected function removeDiscountFromSubscription(CashierSubscription $subscription, bool $adminView = false)
	{

		try {

			$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

			// Load fresh Stripe subscription and expand discount fields
			$stripeSub = $stripe->subscriptions->retrieve($subscription->stripe_id, [
				'expand' => [
					'discount',
					'discounts',
					'items.data.discounts',
				],
			]);

			Log::channel('stripepayments')->info('Before discount removal', [
				'local_subscription_id' => $subscription->id,
				'stripe_subscription_id' => $subscription->stripe_id,
				'subscription_discount_id' => $stripeSub->discount->id ?? null,
				'subscription_discounts_count' => !empty($stripeSub->discounts?->data) ? count($stripeSub->discounts->data) : 0,
				'item_discounts' => collect($stripeSub->items->data ?? [])->map(function ($item) {
					return [
						'item_id' => $item->id ?? null,
						'discounts_count' => !empty($item->discounts?->data) ? count($item->discounts->data) : 0,
					];
				})->all(),
			]);

			$removedSomething = false;

			// Try to remove subscription-level discount unconditionally.
			try {
				$stripe->subscriptions->deleteDiscount($subscription->stripe_id, []);
				$removedSomething = true;
			} catch (\Throwable $e) {
				Log::channel('stripepayments')->info('Subscription-level deleteDiscount did not remove anything', [
					'local_subscription_id' => $subscription->id,
					'stripe_subscription_id' => $subscription->stripe_id,
					'message' => $e->getMessage(),
				]);
			}

			// Remove item-level discounts too, if any exist
			if (!empty($stripeSub->items) and !empty($stripeSub->items->data)) {
				foreach ($stripeSub->items->data as $item) {
					try {
						if (!empty($item->discounts) and !empty($item->discounts->data)) {
							$stripe->subscriptionItems->update($item->id, [
								'discounts' => [],
							]);
							$removedSomething = true;
						}
					} catch (\Throwable $e) {
						Log::channel('stripepayments')->warning('Failed removing item-level discount', [
							'local_subscription_id' => $subscription->id,
							'stripe_subscription_id' => $subscription->stripe_id,
							'stripe_subscription_item_id' => $item->id ?? null,
							'message' => $e->getMessage(),
						]);
					}
				}
			}

			// Re-fetch Stripe subscription after deletion
			$freshStripeSub = $stripe->subscriptions->retrieve($subscription->stripe_id, [
				'expand' => [
					'discount',
					'discounts',
					'items.data.discounts',
				],
			]);

			$stillHasSubscriptionDiscount =
				(!empty($freshStripeSub->discount) and !empty($freshStripeSub->discount->id))
				|| (!empty($freshStripeSub->discounts) and !empty($freshStripeSub->discounts->data));

			$stillHasItemDiscount = false;
			if (!empty($freshStripeSub->items) and !empty($freshStripeSub->items->data)) {
				foreach ($freshStripeSub->items->data as $item) {
					if (!empty($item->discounts) and !empty($item->discounts->data)) {
						$stillHasItemDiscount = true;
						break;
					}
				}
			}

			Log::channel('stripepayments')->info('After discount removal', [
				'local_subscription_id' => $subscription->id,
				'stripe_subscription_id' => $subscription->stripe_id,
				'still_has_subscription_discount' => $stillHasSubscriptionDiscount,
				'still_has_item_discount' => $stillHasItemDiscount,
				'subscription_discount_id' => $freshStripeSub->discount->id ?? null,
				'subscription_discounts_count' => !empty($freshStripeSub->discounts?->data) ? count($freshStripeSub->discounts->data) : 0,
				'item_discounts' => collect($freshStripeSub->items->data ?? [])->map(function ($item) {
					return [
						'item_id' => $item->id ?? null,
						'discounts_count' => !empty($item->discounts?->data) ? count($item->discounts->data) : 0,
					];
				})->all(),
			]);

			// Clear local payment meta only if Stripe really no longer has discount
			if (!$stillHasSubscriptionDiscount and !$stillHasItemDiscount) {

				$payment = UserPayment::where('stripe_subscription_id', $subscription->stripe_id)
					->latest('id')
					->first();

				if ($payment) {
					$meta = is_array($payment->metadata) ? $payment->metadata : [];

					unset(
						$meta['stripe_coupon_id'],
						$meta['user_discount_amount'],
						$meta['user_discount_percent'],
						$meta['user_discount_euros']
					);

					$payment->update([
						'metadata' => $meta,
					]);
				}

				return back()->with('message', trans('stripe-products.subscription-discount-removed'));

			}

			return back()->with('notice', trans('stripe-products.subscription-discount-remove-failed'));

		} catch (\Throwable $e) {

			Log::channel('stripepayments')->error('Failed to remove subscription discount', [
				'local_subscription_id' => $subscription->id,
				'stripe_subscription_id' => $subscription->stripe_id,
				'message' => $e->getMessage(),
			]);

			return back()->with('notice', trans('stripe-products.subscription-discount-remove-failed'));

		}

	}

}
