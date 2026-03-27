<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Validator;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\UserPayment;
use App\Models\UserTransactions;

use App\Services\StripeCatalogService;

use Illuminate\Http\Request;

class CheckoutController extends Controller
{

	/**
	 * Open onetime payment view
	 */
	public function oneOff(
		Request $request,
		Product $product,
		?int $amount = null,
		?string $currency = null,
		?int $entryId = null,
		?int $dateId = null,
		?int $transactionId = null,
		$redir = true
	)
	{

		// Define onetime payment variables
		$amt = $amount ?? $request->input('amount');
		$cur = $currency ?? $request->input('currency');

		// Validate fields only if sent from form
		if ($amount === null or $currency === null){

			$request->merge(['amount' => $amt, 'currency' => $cur]);

			$validator = Validator::make($request->all(), [
				'amount'	=> ['required','integer','min:100'],
				'currency'	=> ['required','string','max:10'],
			]);

			if ($validator->fails()) {

				return back()
					->with('notice', trans(($amt < 100 ? 'stripe-products.more-than-100-cents' : 'racster.all-fields-are-required')))
					->withInput($request->all())
					->withErrors($validator);

			}

		}

		$user = $request->user();
		$user->createOrGetStripeCustomer();

		// Create local payment "pending" row
		$payment = UserPayment::create([
			'user_id'			=> $user->id,
			'product_id'		=> $product->id,
			'entry_id'			=> $entryId,
			'date_id'			=> $dateId,
			'status'			=> 'pending',
		]);

		$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
		$session = $stripe->checkout->sessions->create([
			'mode'					=> 'payment',
			'customer'				=> $user->stripe_id,
			'success_url'			=> route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url'			=> route('checkout.cancel').'?session_id={CHECKOUT_SESSION_ID}',
			'automatic_tax'			=> ['enabled' => false],
			'line_items'			=> [
				[
					'price_data'	=> [
						'currency'		=> $cur,
						'unit_amount'	=> $amt,
						'product_data'	=> [
							'name'		=> $product->name,
							'metadata'	=> ['laravel_product_id' => (string) $product->id],
						],
					],
					'quantity'		=> 1,
				]
			],
			'invoice_creation'		=> [
				'enabled'		=> true,
				'invoice_data'	=> [
					'metadata'	=> [
						'payment_id' => (string) $payment->id,
						'laravel_product_id' => (string) $product->id,
					],
				],
			],
			'payment_intent_data'	=> [
				'metadata' => ['payment_id' => (string) $payment->id],
			],
		]);

		// Save the checkout session ID locally
		$payment->update([
			'stripe_checkout_session_id' => $session->id,
		]);

		if (empty($transactionId)){

			// Add client transaction if it does not exist
			$transaction = UserTransactions::createTransaction('onhold', [
				'amount'	=> ((!empty($amt) and $amt > 0) ? ($amt/100) : null),
				'comment'	=> trans('racster.transaction-attending-comment'),
				'date_id'	=> $dateId,
				'user_id'	=> $user->id,
			]);

			// Define transaction ID
			$transactionId = $transaction->id;

		}

		// Update client transaction
		UserTransactions::updateTransaction($transactionId, [
			'stripe_sess_id' => $session->id,
		]);

		if ($redir !== true){
			return $session->url;
		}else{
			return redirect($session->url);
		}

	}

	/**
	 * Attend user to pre made entry date
	 */
	public function attend_entry_date(Request $request, $dateID){

		if (!empty($dateID)){

			// Get entry date
			$entry_date = DB::table('racster_entry_dates as date')
				->select('date.*', 'client.id as cid', 'client.user_quantity')
				->leftJoin('racster_entry_users as client', function($join){
					$join->on('client.entry_id', '=', 'date.entry_id');
					$join->on('client.date_id', '=', 'date.id');
					$join->where('client.user_type', 'client');
					$join->where('client.user_id', Auth::user()->id);
					$join->where('client.paying', 1);
					$join->whereNull('client.deleted_at');
				})
				->where('date.id', $dateID)
				->whereNotNull('client.id')
				->whereNull('date.deleted_at')
				->first();

			if (!empty($entry_date)){

				// Check if client transaction exists
				$transaction = UserTransactions::getUserDateTransaction(['onhold'], $entry_date->id);

				if (!empty($transaction) and !empty($transaction->id)){

					// Define entry price
					$entry_price = $entry_date->entry_price;

					// Add discount if client has discount
					if (!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry_date->extra_price != 1){
						$entry_price = $entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0);
					}

					// Update client transaction
					UserTransactions::updateTransaction($transaction->id, [
						'transaction_amount'	=> $entry_price,
					]);

					// Get first active oneoff product
					$product = Product::query()
						->active()
						->oneOff()
						->whereHas('oneOffPrices')
						->with('oneOffPrices')
						->first();

					if (!empty($product)){

						// Create Stripe checkout url
						return $this->oneOff(
							request(),
							$product,
							amount: ($entry_price*100),
							currency: strtolower(config('racster.main-currency')),
							entryId: $entry_date->entry_id,
							dateId: $entry_date->id,
							transactionId: $transaction->id,
						);

					}
				
				}

			}

		}

		return redirect(route('nouser'))
			->with('notice', trans('racster.no-rights-for-op'));

	}

	/**
	 * Open subscription payment view
	 */
	public function subscribe(
		Request $request,
		Product $product,
		StripeCatalogService $catalog,
		?int $productPriceId = null,
		?int $unitAmount = null,
		?string $currency = null,
		?string $interval = null,
		?int $entryId = null,
		?int $dateId = null,
		?int $transactionId = null,
		$redir = true
	)
	{

		// Define subscription variables
		$productPriceId	= $productPriceId ?? $request->input('product_price_id');
		$unitAmount = $unitAmount ?? $request->input('unit_amount');
		$currency = $currency ?? $request->input('currency');
		$interval = $interval ?? $request->input('interval');

		if ($productPriceId){

			// Validate only form request
			if (!$request->has('product_price_id')){
				// when passed via arg, skip request validation
			} else {
				$request->validate([
					'product_price_id' => ['required','integer','exists:stripe_product_prices,id'],
				]);
			}

			/** @var ProductPrice $price */
			$price = ProductPrice::query()->findOrFail($productPriceId);

			// Ensure the price belongs to the given product and is active & recurring
			abort_unless($price->product_id === $product->id and $price->active, 404);
			abort_unless(!is_null($price->interval), 422); // subscriptions require month/year

		} else {

			// Validate only what came from request
			if ($request->hasAny(['unit_amount','currency','interval'])){

				$request->merge([
					'unit_amount'	=> $unitAmount,
					'currency'		=> $currency,
					'interval'		=> $interval,
				]);

				$validator = Validator::make($request->all(), [
					'unit_amount'	=> ['required','integer','min:100'],
					'currency'		=> ['required','string','max:10'],
					'interval'		=> ['required','in:month,year'],
				]);

				if ($validator->fails()) {

					return back()
						->with('notice', trans(($unitAmount < 100 ? 'stripe-products.more-than-100-cents' : 'racster.all-fields-are-required')))
						->withInput($request->all())
						->withErrors($validator);

				}

			}

			// Basic guard when provided via args
			abort_unless($unitAmount and $currency and in_array($interval, ['month','year'], true), 422);

			$price = $catalog->findOrCreateStripePrice($product, $unitAmount, $currency, $interval);

		}

		// Make sure the Stripe Product exists (no-op if already set)
		$catalog->ensureStripeProduct($product);

		// Ensure the selected price has a Stripe Price ID (if admin created locally only)
		if (!$price->stripe_price_id){
			$price = $catalog->findOrCreateStripePrice($product, $price->unit_amount, $price->currency, $price->interval);
		}

		$user = $request->user();
		$user->createOrGetStripeCustomer();

		// Create local payment "pending" row
		$payment = UserPayment::create([
			'user_id'			=> $user->id,
			'product_id'		=> $product->id,
			'product_price_id'	=> $price->id,
			'entry_id'			=> $entryId,
			'date_id'			=> $dateId,
			'status'			=> 'pending',
		]);

		// Otherwise, use Stripe Checkout to collect PM + start sub
		$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
		$session = $stripe->checkout->sessions->create([
			'mode'				=> 'subscription',
			'customer'			=> $user->stripe_id,
			'success_url'		=> route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url'		=> route('checkout.cancel').'?session_id={CHECKOUT_SESSION_ID}',
			'line_items'		=> [
				[
					'price'		=> $price->stripe_price_id,
					'quantity'	=> 1,
				]
			],
			'metadata'			=> [
				'payment_id'			=> (string) $payment->id,
				'laravel_product_id'	=> (string) $product->id,
				'laravel_price_id'		=> (string) $price->id,
			],
			'subscription_data'	=> [
				'metadata'	=> [
					'payment_id'			=> (string) $payment->id,
					'laravel_product_id'	=> (string) $product->id,
					'laravel_price_id'		=> (string) $price->id,
				],
			],
		]);

		// Save the checkout session ID locally
		$payment->update([
			'stripe_checkout_session_id' => $session->id
		]);

		if (empty($transactionId)){

			// Add client transaction if it does not exist
			$transaction = UserTransactions::createTransaction('onhold', [
				'amount'	=> ((!empty($unitAmount) and $unitAmount > 0) ? ($unitAmount/100) : null),
				'comment'	=> trans('racster.transaction-attending-comment'),
				'date_id'	=> $dateId,
				'user_id'	=> $user->id,
			]);

			// Define transaction ID
			$transactionId = $transaction->id;

		}

		// Update client transaction
		UserTransactions::updateTransaction($transactionId, [
			'stripe_sess_id' => $session->id,
		]);

		if ($redir !== true){
			return $session->url;
		}else{
			return redirect($session->url);
		}

	}

	/**
	 * Subscribe user to entry
	 *
	 * New flow:
	 * 1) charge full first month as one-off payment now
	 * 2) save payment method for off-session usage
	 * 3) webhook creates future recurring subscription
	 */
	public function subscribe_entry(Request $request, $entryID){

		if (!empty($entryID)){

			// Get entry data
			$entry_data = DB::table('racster_entries as entry')
				->select('entry.*', 'client.id as cid')
				->leftJoin('racster_entry_users as client', function($join){
					$join->on('client.entry_id', '=', 'entry.id');
					$join->where('client.user_type', 'client');
					$join->where('client.user_id', Auth::user()->id);
					$join->where('client.paying', 1);
					$join->whereNull('client.deleted_at');
				})
				->where('entry.id', $entryID)
				->whereNull('entry.deleted_at')
				->first();

			if (!empty($entry_data) and !empty($entry_data->entry_monthly_fee)){

				// For first payment use one-off product
				$product = Product::query()
					->active()
					->oneOff()
					->first();

				if (!empty($product)){
					return $this->startEntryInitialPaymentCheckout($request, $product, $entry_data);
				}

			}

		}

		return redirect(route('nouser'))
			->with('notice', trans('racster.no-rights-for-op'));

	}

	/**
	 * First full payment for entry subscription flow.
	 * Webhook will create the recurring subscription after this is paid.
	 */
	protected function startEntryInitialPaymentCheckout(Request $request, Product $product, object $entryData)
	{

		$user = $request->user();
		$user->createOrGetStripeCustomer();

		// Base monthly fee is stored in euros
		$baseAmountEuros = (int) $entryData->entry_monthly_fee;

		// Define payment discount
		$discountPercent = max(0, (int) ($user->discount_amount ?? 0));

		// Calculate discount locally in FULL EUROS
		$discountEuros = 0;
		if ($discountPercent > 0 && $baseAmountEuros > 0) {
			$discountEuros = (int) round(($baseAmountEuros * $discountPercent) / 100, 0);
			$discountEuros = min($discountEuros, $baseAmountEuros);
		}

		// Final payable amount in euros, then convert to cents
		$finalAmountEuros = max(0, $baseAmountEuros - $discountEuros);
		$amount = $finalAmountEuros * 100;

		// Define payment currency
		$currency = strtolower($product->currency ?: config('racster.main-currency'));

		$payment = UserPayment::create([
			'user_id'		=> $user->id,
			'product_id'	=> $product->id,
			'entry_id'		=> $entryData->id,
			'amount'		=> $amount,
			'currency'		=> $currency,
			'status'		=> 'pending',
			'metadata'		=> [
				'flow'						=> 'entry_subscription_initial',
				'user_discount_percent'		=> (string) $discountPercent,
				'user_discount_euros'		=> (string) $discountEuros,
				'user_discount_amount'		=> (string) ($discountEuros * 100), // cents
				'base_amount_euros'			=> (string) $baseAmountEuros,
				'final_amount_euros'		=> (string) $finalAmountEuros,
			],
		]);

		$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

		$session = $stripe->checkout->sessions->create([
			'mode'			=> 'payment',
			'customer'		=> $user->stripe_id,
			'success_url'	=> route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
			'cancel_url'	=> route('checkout.cancel').'?session_id={CHECKOUT_SESSION_ID}',
			'automatic_tax'	=> ['enabled' => false],

			'line_items'	=> [[
				'price_data'	=> [
					'currency'		=> $currency,
					'unit_amount'	=> $amount,
					'product_data'	=> [
						'name'			=> $product->name,
						'metadata'		=> [
							'laravel_product_id'	=> (string) $product->id,
							'entry_id'				=> (string) $entryData->id,
						],
					],
				],
				'quantity' => 1,
			]],

			'metadata' => [
				'flow'						=> 'entry_subscription_initial',
				'payment_id'				=> (string) $payment->id,
				'entry_id'					=> (string) $entryData->id,
				'laravel_product_id'		=> (string) $product->id,
				'user_discount_percent'		=> (string) $discountPercent,
				'user_discount_euros'		=> (string) $discountEuros,
				'user_discount_amount'		=> (string) ($discountEuros * 100),
			],

			'invoice_creation' => [
				'enabled' => true,
				'invoice_data' => [
					'metadata' => [
						'flow'						=> 'entry_subscription_initial',
						'payment_id'				=> (string) $payment->id,
						'entry_id'					=> (string) $entryData->id,
						'laravel_product_id'		=> (string) $product->id,
						'user_discount_percent'		=> (string) $discountPercent,
						'user_discount_euros'		=> (string) $discountEuros,
						'user_discount_amount'		=> (string) ($discountEuros * 100),
					],
				],
			],

			'payment_intent_data' => [
				'metadata' => [
					'flow'						=> 'entry_subscription_initial',
					'payment_id'				=> (string) $payment->id,
					'entry_id'					=> (string) $entryData->id,
					'laravel_product_id'		=> (string) $product->id,
					'user_discount_percent'		=> (string) $discountPercent,
					'user_discount_euros'		=> (string) $discountEuros,
					'user_discount_amount'		=> (string) ($discountEuros * 100),
				],
				'setup_future_usage' => 'off_session',
			],
		]);

		$payment->update([
			'stripe_checkout_session_id' => $session->id,
		]);

		return redirect($session->url);

	}

	public function success(Request $request)
	{

		return view('checkout.success');

	}

	public function cancel(Request $request){

		$sessionId = $request->query('session_id');

		if ($sessionId){

			// Find the pending payment row by session id
			$payment = UserPayment::where('stripe_checkout_session_id', $sessionId)->first();

			if ($payment){
				$payment->markCancelled();
				if (!empty($payment->date_id)){
					$transaction = UserTransactions::getUserDateTransaction(['onhold'], $payment->date_id, $payment->user_id);
					if (!empty($transaction) and !empty($transaction->id) and $transaction->stripe_sess_id == $sessionId){
						UserTransactions::updateTransaction($transaction->id, [
							'stripe_sess_id'	=> NULL,
						]);
					}
				}
			}

		}

		return view('checkout.cancel');

	}

	public function continue(
		Request $request,
		UserPayment $payment,
		StripeCatalogService $catalog
	){

		// Check if the current user is the payment owner
		if ($request->user() and $payment->user_id !== $request->user()->id){
			abort(403);
		}

		$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

		// Try to reuse previous session ID
		if ($payment->stripe_checkout_session_id){

			try {

				$session = $stripe->checkout->sessions->retrieve($payment->stripe_checkout_session_id, []);

				// status: 'open' | 'complete' | 'expired'
				if (($session->status === 'open' or $session->status === 'pending') and !empty($session->url)){
					return redirect($session->url);
				}

				if ($session->status === 'complete'){

					return redirect()->route('checkout.success', [
						'session_id' => $session->id,
					]);

				}

				// Create new session with 'expired' status

			} catch (\Throwable $e){
				// Create new session if retrieval fails
			}

		}

		// Define success/cancel URLs
		$successUrl = route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}';
		$cancelUrl = route('checkout.cancel').'?session_id={CHECKOUT_SESSION_ID}';

		if (!empty($payment->product_price_id)){

			// SUBSCRIPTION
			$price = ProductPrice::findOrFail($payment->product_price_id);
			$product = $price->product; // ensure relation exists in your model

			// Make sure Stripe product/price exist (no-op if already set)
			$catalog->ensureStripeProduct($product);
			if (!$price->stripe_price_id){
				$price = $catalog->findOrCreateStripePrice(
					$product,
					$price->unit_amount,
					$price->currency,
					$price->interval
				);
			}

			$session = $stripe->checkout->sessions->create([
				'mode'				=> 'subscription',
				'customer'			=> $request->user()->createOrGetStripeCustomer(),
				'success_url'		=> $successUrl,
				'cancel_url'		=> $cancelUrl,
				'line_items'		=> [
					[
						'price'		=> $price->stripe_price_id,
						'quantity'	=> 1,
					]
				],
				'metadata'			=> [
					'payment_id'			=> (string) $payment->id,
					'laravel_product_id'	=> (string) $product->id,
					'laravel_price_id'		=> (string) $price->id,
				],
				'subscription_data'	=> [
					'metadata'	=> [
						'payment_id'			=> (string) $payment->id,
						'laravel_product_id'	=> (string) $product->id,
						'laravel_price_id'		=> (string) $price->id,
					],
				],
			]);

		} else {

			// ONE-OFF
			$product = $payment->product_id ? Product::find($payment->product_id) : null;
			$name = $product?->name ?? 'One-off payment';

			$paymentIntentData = [
				'metadata' => ['payment_id' => (string) $payment->id],
			];

			// Preserve special entry-subscription initial flow when re-opening an expired session
			if (($payment->metadata['flow'] ?? null) === 'entry_subscription_initial'){
				$paymentIntentData['metadata'] = [
					'flow'			=> 'entry_subscription_initial',
					'payment_id'	=> (string) $payment->id,
					'entry_id'		=> (string) $payment->entry_id,
				];
				$paymentIntentData['setup_future_usage'] = 'off_session';
			}

			$session = $stripe->checkout->sessions->create([
				'mode'					=> 'payment',
				'customer'				=> $request->user()->createOrGetStripeCustomer(),
				'success_url'			=> $successUrl,
				'cancel_url'			=> $cancelUrl,
				'line_items'			=> [[
					'price_data'	=> [
						'currency'		=> $payment->currency ?? env('CASHIER_CURRENCY'),
						'unit_amount'	=> $payment->amount, // cents stored locally
						'product_data'	=> [
							'name'		=> $name,
							'metadata'	=> [
								'payment_id'			=> (string) $payment->id,
								'laravel_product_id'	=> $product?->id,
							],
						],
					],
					'quantity'	=> 1,
				]],
				// Create an invoice so invoice.payment_succeeded fires for one-offs too
				'invoice_creation'		=> [
					'enabled' => true,
					'invoice_data' => [
						'metadata' => (($payment->metadata['flow'] ?? null) === 'entry_subscription_initial')
							? [
								'flow'					=> 'entry_subscription_initial',
								'payment_id'			=> (string) $payment->id,
								'entry_id'				=> (string) $payment->entry_id,
								'laravel_product_id'	=> (string) ($product?->id),
							]
							: ['payment_id' => (string) $payment->id],
					],
				],
				'payment_intent_data'	=> $paymentIntentData,
				'metadata'				=> (($payment->metadata['flow'] ?? null) === 'entry_subscription_initial')
					? [
						'flow'					=> 'entry_subscription_initial',
						'payment_id'			=> (string) $payment->id,
						'entry_id'				=> (string) $payment->entry_id,
						'laravel_product_id'	=> (string) ($product?->id),
					]
					: [
						'payment_id' => (string) $payment->id,
					],
			]);

		}

		// Store/replace new session id and keep status pending if not already paid
		$payment->update([
			'stripe_checkout_session_id' => $session->id,
			'status' => $payment->status === 'paid' ? 'paid' : 'pending',
		]);

		return redirect($session->url);

	}

}