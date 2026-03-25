<?php

namespace App\Console\Commands;

use App\Models\UserPayment;
use Illuminate\Console\Command;

class ExpireStalePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user-payments:expire {--minutes=30 : Minutes after which pending payments expire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark stale pending payments as cancelled if no payment completed in time';

    /**
     * Execute the console command.
     */
	public function handle(): int
	{

		$minutes = (int)$this->option('minutes');
		$cutoff  = now()->subMinutes($minutes);

		$stalePayments = UserPayment::where('status', 'pending')
			->where('created_at', '<', $cutoff)
			->whereNull('stripe_invoice_id')
			->get();

		$count = 0;

		foreach ($stalePayments as $payment) {
			$payment->markCancelled('expired');
			$count++;
		}

		$this->info("Expired {$count} stale payment(s).");

		return self::SUCCESS;

	}

}
