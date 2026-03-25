<?php

namespace App\Http\Controllers;

use DB;
use Log;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\UserPayment;
use App\Models\ScheduledEmail;
use App\Services\StripeCatalogService;
use App\Services\SubscriptionEndService;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Laravel\Cashier\Subscription as CashierSubscription;

class StripeWebhookController extends CashierWebhookController
{

	/**
	 * Handle Stripe webhooks
	 */
	public function handleWebhook(Request $request)
	{

		Log::channel('stripepayments')->info('Stripe webhook received', [
			'payload' => $request->all(),
		]);

		return parent::handleWebhook($request);

	}

	/**
	 * Handle completed checkouts
	 */
	public function handleCheckoutSessionCompleted(array $payload)
	{

		$session = $payload['data']['object'] ?? [];
		$paymentId = $session['metadata']['payment_id'] ?? null;

		if ($paymentId){
			UserPayment::whereKey($paymentId)->update([
				'stripe_checkout_session_id' => $session['id'] ?? null,
			]);
		}

		// Create future recurring subscription with successful full one-off payment
		if (
			($session['mode'] ?? null) === 'payment' &&
			($session['payment_status'] ?? null) === 'paid' &&
			($session['metadata']['flow'] ?? null) === 'entry_subscription_initial'
		) {
			$this->createEntrySubscriptionFromInitialCheckout($session);
		}

		return $this->successMethod();

	}

	/**
	 * Handle successful payments
	 */
	public function handleInvoicePaymentSucceeded(array $payload)
	{

		$invoice = $payload['data']['object'] ?? [];
		$subscriptionId = ((!empty($invoice['parent']) and array_key_exists('subscription_details', $invoice['parent'])) ? $invoice['parent']['subscription_details']['subscription'] : null);
		$paymentId = ((!empty($subscriptionId) and array_key_exists('payment_id', $invoice['parent']['subscription_details']['metadata'])) ? $invoice['parent']['subscription_details']['metadata']['payment_id'] : ($invoice['metadata']['payment_id'] ?? null));

		$customerId = $invoice['customer'] ?? null;
		$userId = $customerId ? User::where('stripe_id', $customerId)->value('id') : null;

		// Get product interval for subscription period length
		if (!empty($subscriptionId) and !empty($invoice['parent']['subscription_details']['metadata']['laravel_price_id'])){
			$productprice = ProductPrice::query()->find($invoice['parent']['subscription_details']['metadata']['laravel_price_id']);
			if (!empty($productprice) and !empty($productprice->interval)){
				$priceinterval = $productprice->interval;
			}
		}

		// Get payment period start and end
		$start = null;
		$end = null;
		$subPeriodEnd = null;

		if (!empty($subscriptionId)) {

			$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

			$sub = $stripe->subscriptions->retrieve($subscriptionId);

			// Get subscription start / end from invoice line period for the subscription item
			$linePeriodStart = $invoice['lines']['data'][0]['period']['start'] ?? null;
			$linePeriodEnd = $invoice['lines']['data'][0]['period']['end'] ?? null;

			if (!empty($linePeriodStart) && !empty($linePeriodEnd)) {
				$start = Carbon::createFromTimestamp($linePeriodStart)->setTimezone(config('app.timezone'));
				$end = Carbon::createFromTimestamp($linePeriodEnd)->setTimezone(config('app.timezone'));
			}

			// Fallback: current subscription period
			if (empty($start) || empty($end)) {

				$subCurrentPeriodStart = $sub->current_period_start ?? ($sub->items->data[0]->current_period_start ?? null);
				$subCurrentPeriodEnd = $sub->current_period_end ?? ($sub->items->data[0]->current_period_end ?? null);

				if (!empty($subCurrentPeriodStart) && !empty($subCurrentPeriodEnd)) {
					$start = Carbon::createFromTimestamp($subCurrentPeriodStart)->setTimezone(config('app.timezone'));
					$end = Carbon::createFromTimestamp($subCurrentPeriodEnd)->setTimezone(config('app.timezone'));
				}
			}

			// Last fallback: basic logic
			if (empty($start) || empty($end)) {

				$effectiveAt = $invoice['effective_at'] ?? null;

				if (!empty($effectiveAt)) {
					$start = Carbon::createFromTimestamp($effectiveAt)->setTimezone(config('app.timezone'));
					$end = Carbon::createFromTimestamp(strtotime('+1 ' . (!empty($priceinterval) ? $priceinterval : 'month'), $effectiveAt))->setTimezone(config('app.timezone'));
				}

			}

			// Store current period end separately if you still want it
			if (!empty($sub->current_period_end) || !empty($sub->items->data[0]->current_period_end)) {
				$subPeriodEnd = Carbon::createFromTimestamp(!empty($sub->current_period_end) ? $sub->current_period_end : $sub->items->data[0]->current_period_end)->setTimezone(config('app.timezone'));
			}

		}

		$coveredMonth = (!empty($subscriptionId) && !empty($start)) ? $start->format('m.Y') : null;

		if ($paymentId){

			UserPayment::whereKey($paymentId)
				->update([
					'stripe_invoice_id'			=> $invoice['id'] ?? null,
					'stripe_subscription_id'	=> $subscriptionId,
					'sub_period_end'			=> $subPeriodEnd ?? null,
					'stripe_payment_intent_id'	=> $invoice['payment_intent'] ?? null,
					'amount'					=> $invoice['amount_paid'] ?? null,
					'currency'					=> $invoice['currency'] ?? null,
					'period_start'				=> $start,
					'period_end'				=> $end,
					'covered_month'				=> $coveredMonth,
					'failed_at'					=> null,
					'failed_notice_sent'		=> null,
					'status'					=> ($invoice['paid'] ?? false) ? 'paid' : ($invoice['status'] ?? 'pending'),
					'billing_reason'			=> $invoice['billing_reason'] ?? null,
					'metadata'					=> (!empty($subscriptionId) ? $invoice['parent']['subscription_details']['metadata'] : ($invoice['metadata'] ?? null)),
				]);

			// Get payment info
			$payment = UserPayment::find($paymentId);

		}else{

			// Fallback by invoice id
			$payment = UserPayment::updateOrCreate(
				['stripe_invoice_id' => $invoice['id'] ?? null],
				[
					'user_id'					=> $userId,
					'stripe_subscription_id'	=> $subscriptionId,
					'sub_period_end'			=> $subPeriodEnd ?? null,
					'stripe_payment_intent_id'	=> $invoice['payment_intent'] ?? null,
					'amount'					=> $invoice['amount_paid'] ?? null,
					'currency'					=> $invoice['currency'] ?? null,
					'period_start'				=> $start,
					'period_end'				=> $end,
					'covered_month'				=> $coveredMonth,
					'failed_at'					=> null,
					'failed_notice_sent'		=> null,
					'status'					=> ($invoice['paid'] ?? false) ? 'paid' : ($invoice['status'] ?? 'pending'),
					'billing_reason'			=> $invoice['billing_reason'] ?? null,
					'metadata'					=> (!empty($subscriptionId) ? $invoice['parent']['subscription_details']['metadata'] : ($invoice['metadata'] ?? null)),
				]
			);

		}

		// Update payment status locally
		if (!empty($payment) and (!empty($payment->entry_id) or !empty($payment->date_id))){

			if (!empty($payment->date_id)){

				// Get entry date data
				$entry_date = DB::table('racster_entry_dates')
					->where('id', $payment->date_id)
					->whereNull('deleted_at')
					->first();

				if (!empty($entry_date)){

					// Define transaction main data
					$transaction_data = [
						'creator_id'			=> $payment->user_id,
						'transaction_type'		=> 'added',
						'transaction_amount'	=> (($invoice['amount_paid'] ?? null) ? $invoice['amount_paid'] / 100 : null),
						'transaction_comment'	=> trans('stripe-products.stripe-payment-transaction-comment'),
						'date_id'				=> $entry_date->id,
						'user_id'				=> $payment->user_id,
						'updated_at'			=> Carbon::now(),
					];

					// Check if credit for the payment has already been added
					$tid = DB::table('racster_user_transactions')
						->where('transaction_type', $transaction_data['transaction_type'])
						->where('transaction_amount', $transaction_data['transaction_amount'])
						->where('transaction_comment', $transaction_data['transaction_comment'])
						->where('date_id', $transaction_data['date_id'])
						->where('user_id', $transaction_data['user_id'])
						->whereNull('deleted_at')
						->value('id');

					if (empty($tid)){

						$transaction_data['created_at'] = Carbon::now();

						// Add new credit to client transactions for payment
						DB::table('racster_user_transactions')
							->insert($transaction_data);

					}else{

						// Update existing entry main data
						DB::table('racster_user_transactions')
							->where('id', $tid)
							->whereNull('deleted_at')
							->update($transaction_data);

					}

					// Mark payd onhold transactions as used
					DB::table('racster_user_transactions')
						->where('date_id', $entry_date->id)
						->where('transaction_type', 'onhold')
						->where('user_id', $payment->user_id)
						->whereNull('deleted_at')
						->update([
							'transaction_type' => 'used',
							'updated_at' => Carbon::now(),
						]);

					// Send attendance notice with invoice for date-based payments
					if (!empty($invoice['id']) && !empty($invoice['amount_paid'])) {
						\App\Http\Controllers\TimetableController::sendAttendanceNotice(
							$entry_date,
							($invoice['amount_paid'] / 100),
							$invoice['id'],
							$payment->user_id
						);
					}

				}

			}elseif (!empty($coveredMonth) and !empty($subscriptionId)){

				// Get entry onhold user transactions from dates
				$onhold_transactions = DB::table('racster_entry_dates as date')
					->select('trans.*')
					->leftJoin('racster_user_transactions as trans', function($join) use ($payment){
						$join->on('date.id', '=', 'trans.date_id');
						$join->where('trans.user_id', $payment->user_id);
						$join->whereNull('trans.deleted_at');
					})
					->where('date.entry_id', $payment->entry_id)
					->where('trans.transaction_type', 'onhold')
					->whereBetween('date.entry_start', [
						$start,
						$end,
					])
					->whereNull('date.deleted_at')
					->get();

				if (!empty($onhold_transactions)){

					$used_transactions = [];

					foreach ($onhold_transactions as $transaction){

						// Create array of onhold transaction
						$new_transaction = (array)$transaction;

						// Remove transaction ID
						unset($new_transaction['id']);

						// Add payer as transaction creator and user
						$new_transaction['creator_id'] = $new_transaction['user_id'] = $payment->user_id;

						// Change transaction type
						$new_transaction['transaction_type'] = 'added';

						// Update transaction dates
						$new_transaction['created_at'] = $new_transaction['updated_at'] = Carbon::now();

						// Add Stripe session ID
						$new_transaction['stripe_sess_id'] = $payment->stripe_checkout_session_id;

						// Add new transaction based on payment
						DB::table('racster_user_transactions')
							->insert($new_transaction);

						// Add transaction ID to used transactions array
						$used_transactions[] = $transaction->id;

					}

					if (!empty($used_transactions)){

						// Mark payd onhold transactions as used
						DB::table('racster_user_transactions')
							->whereIn('id', $used_transactions)
							->where('transaction_type', 'onhold')
							->whereNull('deleted_at')
							->update([
								'transaction_type' => 'used',
								'stripe_sess_id' => ((!empty($payment->stripe_checkout_session_id)) ? $payment->stripe_checkout_session_id : null),
								'updated_at' => Carbon::now(),
							]);

						// Mark subscription starting transaction as subscribed
						if (!empty($payment->stripe_checkout_session_id)){

							// Mark payd onhold transactions as used
							DB::table('racster_user_transactions')
								->where('stripe_sess_id', $payment->stripe_checkout_session_id)
								->where('transaction_type', 'onhold')
								->whereNull('deleted_at')
								->update([
									'transaction_type' => 'subscribed',
									'updated_at' => Carbon::now(),
								]);

						}

					}

				}

				if (!empty($payment->stripe_subscription_id)){

					// Check if subscription is active
					$sub = CashierSubscription::where('stripe_id', $payment->stripe_subscription_id)->where('stripe_status', 'active')->first();

					if ($sub){

						// Get the entry furthest ending time of user dates
						$last_date = DB::table('racster_entry_dates as date')
							->select('date.*')
							->leftJoin('racster_entry_users as user', function($join) use ($payment){
								$join->on('date.entry_id', '=', 'user.entry_id');
								$join->on('date.id', '=', 'user.date_id');
								$join->where('user.user_type', 'client');
								$join->where('user.user_id', $payment->user_id);
								$join->where('user.paying', 1);
								$join->whereNull('user.deleted_at');
							})
							->where('date.entry_id', $payment->entry_id)
							->whereNull('date.deleted_at')
							->max('date.entry_ending');

						// Set the furthest entry ending as subscription end date
						app(SubscriptionEndService::class)->setEndByStripeId($payment->stripe_subscription_id, (!empty($last_date) ? Carbon::parse($last_date)->addDays(1) : null));

					}

				}

				// Send successful subscription payment notice to user with invoice
				if (!empty($invoice['billing_reason']) and $invoice['billing_reason'] === 'subscription_cycle'){

					// Find user email by user ID
					$email = User::where('id', $payment->user_id)->value('email');

					if (!empty($email)){

						// Schedule subscription recurring notice email
						ScheduledEmail::create([
							'user_id'		=> $payment->user_id,
							'to_email'		=> $email,
							'subject'		=> trans('racster.subscription-recurring-notice-email-subject'),
							'body'			=> trans('racster.subscription-recurring-notice-email-content', [
								'entryTitle' => $payment->entry?->entry_title,
							]),
							'stripe_invid'	=> $payment->stripe_invoice_id,
							'send_at'		=> Carbon::createFromTimestamp(strtotime('+1 minutes'))->setTimezone(config('app.timezone')),
							'status'		=> 'pending',
						]);

					}

				}

			}

		}

		return $this->successMethod();

	}

	/**
	 * Find or create a forever fixed-amount coupon in cents.
	 */
	protected function findOrCreateForeverAmountCoupon(
		\Stripe\StripeClient $stripe,
		int $amountOffCents,
		string $currency
	): ?string
	{
		if ($amountOffCents <= 0) {
			return null;
		}

		$currency = strtolower($currency);
		$couponId = 'user_discount_' . $amountOffCents . '_' . $currency . '_forever';

		try {
			$coupon = $stripe->coupons->retrieve($couponId, []);

			if (!empty($coupon->id) && empty($coupon->deleted)) {
				return $coupon->id;
			}
		} catch (\Throwable $e) {
			// coupon does not exist yet, create below
		}

		$coupon = $stripe->coupons->create([
			'id' => $couponId,
			'amount_off' => $amountOffCents,
			'currency' => $currency,
			'duration' => 'forever',
			'name' => 'User discount ' . number_format($amountOffCents / 100, 0) . ' ' . strtoupper($currency),
		]);

		return $coupon->id ?? null;
	}

	/**
	 * Create recurring subscription after initial one-off payment succeeded.
	 */
	protected function createEntrySubscriptionFromInitialCheckout(array $session): void
	{
		$paymentId = $session['metadata']['payment_id'] ?? null;
		$entryId = $session['metadata']['entry_id'] ?? null;
		$customerId = $session['customer'] ?? null;
		$paymentIntentId = $session['payment_intent'] ?? null;

		if (!$paymentId || !$entryId || !$customerId || !$paymentIntentId) {
			Log::channel('stripepayments')->warning('Missing required data for entry subscription creation', [
				'session_id' => $session['id'] ?? null,
				'payment_id' => $paymentId,
				'entry_id' => $entryId,
				'customer_id' => $customerId,
				'payment_intent' => $paymentIntentId,
			]);
			return;
		}

		$initialPayment = UserPayment::find($paymentId);
		if (!$initialPayment) {
			return;
		}

		// Prevent duplicates from webhook retries
		$existingRecurring = UserPayment::query()
			->where('entry_id', $entryId)
			->whereNotNull('product_price_id')
			->where(function ($query) use ($initialPayment) {
				$query->where('metadata->initial_payment_id', (string) $initialPayment->id)
					->orWhere('metadata->initial_payment_id', $initialPayment->id);
			})
			->first();

		if ($existingRecurring) {
			return;
		}

		$entry = DB::table('racster_entries')
			->where('id', $entryId)
			->whereNull('deleted_at')
			->first();

		if (!$entry || empty($entry->entry_monthly_fee)) {
			return;
		}

		$user = User::where('stripe_id', $customerId)->first();
		if (!$user) {
			return;
		}

		$subscriptionProduct = Product::query()
			->active()
			->subscription()
			->first();

		if (!$subscriptionProduct) {
			Log::channel('stripepayments')->warning('No active subscription product found for entry subscription flow', [
				'entry_id' => $entryId,
				'payment_id' => $paymentId,
			]);
			return;
		}

		/** @var StripeCatalogService $catalog */
		$catalog = app(StripeCatalogService::class);
		$catalog->ensureStripeProduct($subscriptionProduct);

		$price = $catalog->findOrCreateStripePrice(
			$subscriptionProduct,
			((int) $entry->entry_monthly_fee * 100),
			$subscriptionProduct->currency,
			$subscriptionProduct->interval
		);

		if (!$price->stripe_price_id) {
			$price = $catalog->findOrCreateStripePrice(
				$subscriptionProduct,
				$price->unit_amount,
				$price->currency,
				$price->interval
			);
		}

		$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

		$paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId, []);

		$paymentMethodId = is_string($paymentIntent->payment_method)
			? $paymentIntent->payment_method
			: ($paymentIntent->payment_method->id ?? null);

		if (!$paymentMethodId) {
			Log::channel('stripepayments')->warning('No payment method found on initial payment intent', [
				'payment_intent_id' => $paymentIntentId,
				'session_id' => $session['id'] ?? null,
			]);
			return;
		}

		// Make saved card default for invoices/subscriptions
		$stripe->customers->update($customerId, [
			'invoice_settings' => [
				'default_payment_method' => $paymentMethodId,
			],
		]);

		$trialEnd = $this->first25thAtLeastFourWeeksAfterNextMonday(
			Carbon::createFromTimestamp($session['created'], 'UTC')
		);

		$baseAmountEuros = (int) $entry->entry_monthly_fee;
		$discountPercent = max(0, (int) ($user->discount_amount ?? 0));

		$discountEuros = 0;
		if ($discountPercent > 0 && $baseAmountEuros > 0) {
			$discountEuros = (int) round(($baseAmountEuros * $discountPercent) / 100, 0);
			$discountEuros = min($discountEuros, $baseAmountEuros);
		}

		$discountAmountCents = $discountEuros * 100;
		$couponId = null;

		if ($discountAmountCents > 0) {
			$couponId = $this->findOrCreateForeverAmountCoupon(
				$stripe,
				$discountAmountCents,
				$subscriptionProduct->currency
			);
		}

		$subscriptionPayment = UserPayment::create([
			'user_id'			=> $user->id,
			'product_id'		=> $subscriptionProduct->id,
			'product_price_id'	=> $price->id,
			'entry_id'			=> $entryId,
			'status'			=> 'pending',
			'metadata'			=> [
				'flow'						=> 'entry_subscription_recurring',
				'initial_payment_id'		=> (string) $initialPayment->id,
				'initial_session_id'		=> (string) ($session['id'] ?? ''),
				'user_discount_percent'		=> (string) $discountPercent,
				'user_discount_euros'		=> (string) $discountEuros,
				'user_discount_amount'		=> (string) $discountAmountCents,
				'stripe_coupon_id'			=> (string) ($couponId ?? ''),
			],
		]);

		$payload = [
			'customer' => $customerId,
			'items' => [[
				'price' => $price->stripe_price_id,
			]],
			'default_payment_method' => $paymentMethodId,
			'trial_end' => $trialEnd,
			'metadata' => [
				'flow'						=> 'entry_subscription_recurring',
				'payment_id'				=> (string) $subscriptionPayment->id,
				'initial_payment_id'		=> (string) $initialPayment->id,
				'entry_id'					=> (string) $entryId,
				'laravel_product_id'		=> (string) $subscriptionProduct->id,
				'laravel_price_id'			=> (string) $price->id,
				'user_discount_percent'		=> (string) $discountPercent,
				'user_discount_euros'		=> (string) $discountEuros,
				'user_discount_amount'		=> (string) $discountAmountCents,
				'stripe_coupon_id'			=> (string) ($couponId ?? ''),
			],
		];

		if ($couponId) {
			$payload['discounts'] = [
				['coupon' => $couponId],
			];
		}

		$subscription = $stripe->subscriptions->create($payload, [
			'idempotency_key' => 'entry-recurring-subscription-' . $initialPayment->id,
		]);

		$subscriptionPayment->update([
			'stripe_subscription_id' => $subscription->id,
		]);
	}

	/**
	 * Rule:
	 * next Monday after one-off payment
	 * + 4 weeks
	 * first 25th on or after that date
	 */
	protected function first25thAtLeastFourWeeksAfterNextMonday(Carbon $paymentAt): int
	{
		$base = $paymentAt->copy()->startOfDay();

		/* Do not find next monday
			$nextMonday = $base->copy()->next(Carbon::MONDAY);
			$minStart = $nextMonday->copy()->addDays(28)->startOfDay();
		*/
		$minStart = $base->copy()->addDays(28)->startOfDay();

		$candidate = $minStart->copy()->day(25)->startOfDay();

		if ($candidate->lt($minStart)) {
			$candidate->addMonthNoOverflow()->day(25)->startOfDay();
		}

		return $candidate->timestamp;
	}

	/**
	 * Handle failed payments
	 */
	public function handleInvoicePaymentFailed(array $payload)
	{

		$invoice = $payload['data']['object'] ?? [];
		$invoiceId = $invoice['id'] ?? null;

		$subscriptionId = ((!empty($invoice['parent']) and array_key_exists('subscription_details', $invoice['parent'])) ? $invoice['parent']['subscription_details']['subscription'] : null);
		$paymentId = ((!empty($subscriptionId) and array_key_exists('payment_id', $invoice['parent']['subscription_details']['metadata'])) ? $invoice['parent']['subscription_details']['metadata']['payment_id'] : ($invoice['metadata']['payment_id'] ?? null));

		$customerId = $invoice['customer'] ?? null;
		$userId = $customerId ? User::where('stripe_id', $customerId)->value('id') : null;

		// Find the payment row we should update
		$payment = null;

		if ($paymentId){
			$payment = UserPayment::find($paymentId);
		}
		if (!$payment and $subscriptionId){
			$payment = UserPayment::where('stripe_subscription_id', $subscriptionId)->latest('id')->first();
		}
		if (!$payment and $invoiceId){
			$payment = UserPayment::where('stripe_invoice_id', $invoiceId)->first();
		}

		$data = [
			'user_id'					=> $payment?->user_id ?? $userId,
			'stripe_invoice_id'			=> $invoiceId,
			'stripe_subscription_id'	=> $subscriptionId,
			'stripe_payment_intent_id'	=> $invoice['payment_intent'] ?? ($payment->stripe_payment_intent_id ?? null),
			'amount'					=> $invoice['amount_due'] ?? $payment?->amount,
			'currency'					=> $invoice['currency'] ?? $payment?->currency,
			'status'					=> 'failed',
			'failed_at'					=> Carbon::now(),
			'failed_notice_sent'		=> null,
			'billing_reason'			=> $invoice['billing_reason'] ?? $payment?->billing_reason,
			'metadata'					=> (!empty($subscriptionId) ? $invoice['parent']['subscription_details']['metadata'] : ($invoice['metadata'] ?? $payment?->metadata)),
		];

		if ($payment){
			$payment->fill($data)->save();
		} else {
			UserPayment::create($data);
		}

		return $this->successMethod();

	}

	/**
	 * Add locally paused subscription info
	 */
	public function handleCustomerSubscriptionUpdated(array $payload)
	{

		// Let Cashier do its handling
		$response = parent::handleCustomerSubscriptionUpdated($payload);

		$stripeSub = $payload['data']['object'] ?? null;
		if (!$stripeSub){
			return $response;
		}

		$sub = CashierSubscription::where('stripe_id', $stripeSub['id'])->first();
		if (!$sub){
			return $response;
		}

		$pause = $stripeSub['pause_collection'] ?? null;

		$sub->is_paused = $pause ? 1 : 0;
		$sub->pause_behavior = $pause['behavior'] ?? null;
		$sub->pause_resumes_at = isset($pause['resumes_at']) ? Carbon::createFromTimestamp($pause['resumes_at'])->setTimezone(config('app.timezone')) : null;

		// Update paused date and time
		if ($pause and is_null($sub->paused_at)){

			$sub->paused_at = now();

			// Levipro - remove user from entry dates between "paused_at" and "pause_resumes_at"; mark user deleted at entry users list; mark user entry dates transactions deleted;

		}

		if (!$pause){

			$sub->paused_at = null;

			// Levipro - add user to entry dates from "now" on; add user to entry users list; add user transactions for entry dates;

		}

		$sub->save();

		return $response;

	}

	/**
	 * Handle subscription deletion
	 */
	public function handleCustomerSubscriptionDeleted(array $payload)
	{

		Log::channel('stripepayments')->info('Customer subscription deleted', [
			'payload' => $payload,
		]);

		$sub = $payload['data']['object'] ?? [];

		$subscriptionId = $sub['id'] ?? null;
		$paymentIdMeta = $sub['metadata']['payment_id'] ?? null;

		$customerId = $sub['customer'] ?? null;
		$userId = $customerId ? User::where('stripe_id', $customerId)->value('id') : null;

		$currentPeriodEnd = (!empty($sub['current_period_end']) ? Carbon::createFromTimestamp($sub['current_period_end'])->setTimezone(config('app.timezone')) : null);

		// Find the most relevant local payment row to tag as canceled
		$payment = null;

		if ($paymentIdMeta){
			$payment = UserPayment::find($paymentIdMeta);
		}
		if (!$payment and $subscriptionId){
			$payment = UserPayment::where('stripe_subscription_id', $subscriptionId)->latest('id')->first();
		}

		if ($payment){

			$updates = [
				'status'					=> 'canceled',
				'stripe_subscription_id'	=> $subscriptionId,
				'user_id'					=> $payment->user_id ?? $userId,
				'failed_at'					=> null,
				'failed_notice_sent'		=> null,
			];

			if ($currentPeriodEnd and empty($payment->period_end)){
				$updates['period_end'] = $currentPeriodEnd;
				$updates['covered_month'] = $payment->covered_month ?? $currentPeriodEnd->copy()->startOfMonth()->format('Y-m');
			}

			$payment->update($updates);

			// Get user future dates to be cancelled
			$manage_dates = DB::table('racster_entry_users as user')
				->join('racster_entry_dates as date', function ($join) {
					$join->on('date.id', '=', 'user.date_id');
					$join->on('date.entry_id', '=', 'user.entry_id');
					$join->whereNull('date.deleted_at');
				})
				->where('date.entry_start', '>=', Carbon::now())
				->where('user.entry_id', $payment->entry_id)
				->where('user.user_id', $payment->user_id)
				->whereNull('user.deleted_at')
				->pluck('date.client_count', 'date.id')
				->toArray();

			if (!empty($manage_dates)){

				// Get date related transactions
				$manage_transactions = DB::table('racster_user_transactions')
					->where('transaction_type', 'added')
					->whereIn('date_id', array_keys($manage_dates))
					->where('user_id', $payment->user_id)
					->where('stripe_sess_id', $payment->stripe_checkout_session_id)
					->whereNull('deleted_at')
					->get();

				foreach ($manage_transactions as $transaction){

					// Add cancelled transaction amounts back to user
					\App\Models\UserTransactions::createTransaction('added', [
						'amount'	=> $transaction->transaction_amount,
						'comment'	=> trans('racster.transaction-cancelling-attendance-comment'),
						'date_id'	=> $transaction->date_id,
						'user_id'	=> $payment->user_id,
					]);

				}

				// Remove clients from entry future dates
				DB::table('racster_entry_users')
					->where('entry_id', $payment->entry_id)
					->whereIn('date_id', array_keys($manage_dates))
					->where('user_type', 'client')
					->where('user_id', $payment->user_id)
					->whereNull('deleted_at')
					->update([
						'deleted_at' => Carbon::now(),
					]);

				foreach ($manage_dates as $dateID => $client_count){

					// Define updatable date data
					$datedata = [
						'client_count' => (!empty($client_count) ? ($client_count-1) : 0),
						'updated_at' => Carbon::now(),
					];

					// Make private event public without clients
					if (empty($client_count) or ($client_count-1) < 1){
						$datedata['private_entry'] = 0;
					}

					// Update client count in entry date rows
					DB::table('racster_entry_dates')
						->where('id', $dateID)
						->where('entry_id', $payment->entry_id)
						->whereNull('deleted_at')
						->update($datedata);

				}

			}

		} else {

			UserPayment::create([
				'user_id'					=> $userId,
				'stripe_subscription_id'	=> $subscriptionId,
				'status'					=> 'canceled',
				'period_end'				=> $currentPeriodEnd,
				'covered_month'				=> $currentPeriodEnd ? $currentPeriodEnd->copy()->startOfMonth()->format('Y-m') : null,
				'metadata'					=> $sub['metadata'] ?? null,
			]);

		}

		return $this->successMethod();

	}

}
