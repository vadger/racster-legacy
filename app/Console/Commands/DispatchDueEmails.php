<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledEmailJob;
use App\Models\ScheduledEmail;
use Illuminate\Console\Command;

class DispatchDueEmails extends Command
{

	protected $signature = 'emails:dispatch-due {--limit=500}';
	protected $description = 'Dispatch queued jobs for emails whose send_at has passed';

	public function handle(): int
	{

		$limit = (int) $this->option('limit');

		ScheduledEmail::query()
			->where('status', 'pending')
			->where('send_at', '<=', now())
			->orderBy('send_at')
			->limit($limit)
			->get()
			->each(function ($email) {
				$email->update(['status' => 'queued']);
				dispatch(new SendScheduledEmailJob($email->id));
			});

		$this->info('Dispatched due emails.');

		return self::SUCCESS;

	}

}
