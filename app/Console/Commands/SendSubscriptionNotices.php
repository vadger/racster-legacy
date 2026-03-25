<?php

namespace App\Console\Commands;

use Log;

use App\Models\User;
use App\Models\UserPayment;
use App\Models\ScheduledEmail;

use Carbon\Carbon;

use Illuminate\Console\Command;
use Laravel\Cashier\Subscription as CashierSubscription;

class SendSubscriptionNotices extends Command
{

	protected $signature = 'subscriptions:send-notices';
	protected $description = 'Send subscription reminder and dunning emails.';

	public function handle(): int
	{

		Log::channel('subsnotices')->info('Sending subscription notices');

		$this->sendPrechargeReminders();
		$this->sendFailureSequence();

		Log::channel('subsnotices')->info('Subscription notices processed.');

		return self::SUCCESS;

	}

	/**
	 * 1 day before next charge for active subscriptions.
	 */
	protected function sendPrechargeReminders(): void
	{

		$today = Carbon::today();
		$tomorrow = $today->copy()->addDays(3);

		// Get active subscriptions
		$subs = CashierSubscription::query()
			->whereIn('stripe_status', ['active', 'trialing'])
			->with('user')
			->get();

		foreach ($subs as $sub) {

			$user = $sub->user;
			if (!$user) {
				continue;
			}

			// Find user payments that end tomorrow
			$payments = UserPayment::query()
				->where('user_id', $user->id)
				->where('stripe_subscription_id', $sub->stripe_id)
				->where('status', 'paid')
				->whereDate('sub_period_end', $tomorrow)
				->with('entry')
				->get();

			foreach ($payments as $payment) {

				// Schedule subscription precharge notice email
				ScheduledEmail::create([
					'user_id'	=> $user->id,
					'to_email'	=> $user->email,
					'subject'	=> trans('racster.subscription-precharge-notice-email-subject'),
					'body'		=> trans('racster.subscription-precharge-notice-email-content', [
						'endDate' => Carbon::parse($payment->sub_period_end)->format('d.m.Y'),
						'entryTitle' => $payment->entry?->entry_title,
					]),
					'send_at'	=> Carbon::createFromTimestamp(strtotime('+1 minutes'))->setTimezone(config('app.timezone')),
					'status'	=> 'pending',
				]);

				Log::channel('subsnotices')->info('Subscription precharge reminder sent to user', [
					'subscription' => $sub->stripe_id,
					'email' => $user->email,
				]);

			}

		}

	}

	/**
	 * Send notice 0, 5, 10, 20 days / month after failed payment
	 */
	protected function sendFailureSequence(): void
	{

		$now = Carbon::now()->startOfSecond();

		// Get failed Subscriptions
		$subs = CashierSubscription::query()
			->whereIn('stripe_status', ['past_due', 'unpaid'])
			->with('user')
			->get();

		foreach ($subs as $sub) {

			$user = $sub->user;
			if (!$user) {
				continue;
			}

			// Define notice sending day steps
			$noticeDays = config('racster.failed-subscription-steps');

			// Find user payments related to subscription
			$payments = UserPayment::query()
				->where('user_id', $user->id)
				->where('stripe_subscription_id', $sub->stripe_id)
				->where(function ($query) {
					$query->whereNull('failed_notice_sent');
					$query->orWhereNot('failed_notice_sent', config('racster.failed-subscription-laststep'));
				})
				->where('status', 'failed')
				->whereNotNull('failed_at')
				->get();

			foreach ($payments as $payment) {

				$failedAt = Carbon::parse($payment->failed_at)->startOfSecond();
				$days = $failedAt->diffInDays($now);

				$currentStep = $payment->failed_notice_sent;

				if ($days < config('racster.failed-subscription-maxday')){

					// Determine index of current step (or -1 if none)
					$currentIndex = array_search($currentStep, $noticeDays, true);
					if ($currentIndex === false) {
						$currentIndex = -1;
					}

					// Determine the next step
					$nextIndex = $currentIndex + 1;

					// Do day based actions
					if (isset($noticeDays[$nextIndex])) {

						$step = $noticeDays[$nextIndex];

						if ($days >= $step) {

							$this->sendFailureMail($user, $payment, $step);
							$payment->update(['failed_notice_sent' => $step]);

							if ($step == config('racster.failed-subscription-laststep')){

								try {

									// Cancel failed subscription
									$sub->cancelNow();

									ScheduledEmail::create([
										'user_id'		=> $user->id,
										'to_email'		=> config('racster.failed-subscription-admin-email'),
										'subject'		=> trans('racster.subscription-failed-admin-notice-email-subject'),
										'body'			=> trans('racster.subscription-failed-admin-notice-email-content', [
											'failDate' => Carbon::parse($payment->failed_at)->format('d.m.Y'),
											'entryTitle' => $payment->entry?->entry_title,
										]),
										'stripe_invid'	=> $payment->stripe_invoice_id,
										'send_at'		=> Carbon::createFromTimestamp(strtotime('+1 minutes'))->setTimezone(config('app.timezone')),
										'status'		=> 'pending',
									]);

									Log::channel('subsnotices')->info('User subscription canceled', [
										'dayno' => $step,
										'subscription' => $sub->stripe_id,
										'email' => $user->email,
									]);

								} catch (\Throwable $e) {

									Log::channel('subsnotices')->error('Subscription notices processed.', [
										'dayno' => $step,
										'subscription' => $sub->stripe_id,
										'email' => $user->email,
										'message' => $e->getMessage(),
									]);

								}

							}

						}

						continue;

					}

				}

				if (config('racster.failed-subscription-laststep') == 30){

					// Do final step (1 month)
					$oneMonthMark = $failedAt->copy()->addMonth();

					if ($currentStep === end($noticeDays) and $now->greaterThanOrEqualTo($oneMonthMark)) {

						$this->sendFailureMail($user, $payment, config('racster.failed-subscription-laststep'));
						$payment->update(['failed_notice_sent' => $failedAt->diffInDays($oneMonthMark)]);

						try {

							// Cancel failed subscription
							$sub->cancelNow();

							ScheduledEmail::create([
								'user_id'		=> $user->id,
								'to_email'		=> config('racster.failed-subscription-admin-email'),
								'subject'		=> trans('racster.subscription-failed-admin-notice-email-subject'),
								'body'			=> trans('racster.subscription-failed-admin-notice-email-content', [
									'failDate' => Carbon::parse($payment->failed_at)->format('d.m.Y'),
									'entryTitle' => $payment->entry?->entry_title,
								]),
								'stripe_invid'	=> $payment->stripe_invoice_id,
								'send_at'		=> Carbon::createFromTimestamp(strtotime('+1 minutes'))->setTimezone(config('app.timezone')),
								'status'		=> 'pending',
							]);

							Log::channel('subsnotices')->info('User subscription canceled', [
								'dayno' => $step,
								'subscription' => $sub->stripe_id,
								'email' => $user->email,
							]);

						} catch (\Throwable $e) {

							Log::channel('subsnotices')->error('Subscription notices processed.', [
								'dayno' => $step,
								'subscription' => $sub->stripe_id,
								'email' => $user->email,
								'message' => $e->getMessage(),
							]);

						}

					}

				}

			}

		}

	}

	/**
	 * Send failed notice email
	 */
	protected function sendFailureMail(User $user, UserPayment $payment, int $day): void
	{

		// Schedule subscription failed notice email
		ScheduledEmail::create([
			'user_id'		=> $user->id,
			'to_email'		=> $user->email,
			'subject'		=> trans('racster.subscription-failed-notice-email-subject-'.$day),
			'body'			=> trans('racster.subscription-failed-notice-email-content-day-'.$day, [
				'failDate' => Carbon::parse($payment->failed_at)->format('d.m.Y'),
				'entryTitle' => $payment->entry?->entry_title,
			]),
			'stripe_invid'	=> $payment->stripe_invoice_id,
			'send_at'		=> Carbon::createFromTimestamp(strtotime('+1 minutes'))->setTimezone(config('app.timezone')),
			'status'		=> 'pending',
		]);

		// Define client phone number and clean it up from other than numbers and symbol "+"
		$user_phone = preg_replace('/[^0-9+]+/', '', trim($user->mobile_country_code).trim($user->mobile_number));

		// Validate phone number
		$validator = Validator::make(['phone' => $user_phone], ['phone' => ['required', 'phone:INTERNATIONAL', 'starts_with:+']]);

		if (!empty($user_phone) and strlen($user_phone) >= 7 and substr($user_phone, 0, 1) == '+' and $validator->passes()){

			// Define Text Magic SMS text with meeting date and text language
			$sms_content = Lang::get('racster.sms-reminder-of-failed-payment-'.$day, [], 'et');

			// Send SMS to client
			app('App\Http\Controllers\Api\TextMagicController')->sendSMStoClient($sms_content, [$user_phone]);

		}

		Log::channel('subsnotices')->info('Subscription payment failure sent to user', [
			'dayno' => $day,
			'invoice' => $payment->stripe_invoice_id,
			'email' => $user->email,
		]);

	}

}
