<?php

namespace App\Services;

use Carbon\Carbon;
use Laravel\Cashier\Subscription;
use Stripe\StripeClient;

class SubscriptionEndService
{

	public function __construct(private ?StripeClient $stripe = null)
	{
		$this->stripe ??= new StripeClient(env('STRIPE_SECRET'));
	}

	/**
	 * Set/clear the end date by Stripe subscription ID (sub_...).
	 */
	public function setEndByStripeId(string $stripeSubId, ?Carbon $endsAt = null): Subscription {

		/** @var Subscription $sub */
		$sub = Subscription::where('stripe_id', $stripeSubId)->firstOrFail();

		if ($endsAt === null) {

			$this->stripe->subscriptions->update($stripeSubId, [
				'cancel_at' => null,
				'proration_behavior' => 'none',
			]);

			$sub->forceFill(['ends_at' => null])->save();

		}else{

			// normalize seconds (optional)
			$endsAt = $endsAt->copy()->second(0);

			// Schedule end at specific time
			if ($endsAt->isFuture()) {

				$this->stripe->subscriptions->update($stripeSubId, [
					'cancel_at' => $endsAt->timestamp,
					'proration_behavior' => 'none',
				]);

				$sub->forceFill(['ends_at' => $endsAt])->save();

			}else{

				// endsAt is now/past > cancel immediately
				$this->stripe->subscriptions->cancel($stripeSubId, [
					'invoice_now'	=> false,
					'prorate'		=> false,
				]);

				$sub->forceFill(['ends_at' => now()])->save();

			}
		
		}

		return $sub->refresh();

	}

	public function setEndAfterFullPaidPeriod(string $stripeSubId, ?Carbon $lastEntryEnd): Subscription
	{
		if ($lastEntryEnd === null) {
			return $this->setEndByStripeId($stripeSubId, null);
		}

		$stripeSub = $this->stripe->subscriptions->retrieve($stripeSubId);

		$periodEnd = Carbon::createFromTimestamp(
			$stripeSub->current_period_end
				?? $stripeSub->items->data[0]->current_period_end
		)->setTimezone(config('app.timezone'));

		$interval = $stripeSub->items->data[0]->price->recurring->interval ?? 'month';

		while ($periodEnd->lessThan($lastEntryEnd)) {
			if ($interval === 'year') {
				$periodEnd->addYearNoOverflow();
			} else {
				$periodEnd->addMonthNoOverflow();
			}
		}

		return $this->setEndByStripeId($stripeSubId, $periodEnd);
	}

}
