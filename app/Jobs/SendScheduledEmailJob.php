<?php

namespace App\Jobs;

use App\Mail\ScheduledHtmlMail;
use App\Models\ScheduledEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendScheduledEmailJob implements ShouldQueue
{
	use InteractsWithQueue, Queueable, SerializesModels;

	public function __construct(public int $scheduledEmailId) {}

	public function handle(): void
	{

		$email = ScheduledEmail::find($this->scheduledEmailId);
		if (!$email) return;

		if ($email->status === 'sent') return;

		try {

			Mail::to($email->to_email)->send(
				new ScheduledHtmlMail(
					$email->subject,
					$email->body,
					$email->heading,
					$email->cta_name,
					$email->cta_link,
					$email->stripe_invid
				)
			);

			$email->update([
				'status' => 'sent',
				'sent_at' => now(),
			]);

		} catch (\Throwable $e) {

			$email->increment('attempts');
			$email->update(['status' => 'failed']);
			throw $e;

		}

	}
}
