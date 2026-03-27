<?php

namespace App\Models;

use DB;
use Auth;
use Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserPayment extends Model
{

	protected $table = 'racster_user_payments';
	protected $fillable = [
		'user_id',
		'product_id',
		'product_price_id',
		'entry_id',
		'date_id',
		'stripe_invoice_id',
		'stripe_subscription_id',
		'sub_period_end',
		'stripe_payment_intent_id',
		'stripe_checkout_session_id',
		'amount',
		'currency',
		'period_start',
		'period_end',
		'covered_month',
		'failed_at',
		'failed_notice_sent',
		'status',
		'billing_reason',
		'metadata',
	];

	protected $casts = [
		'period_start'			=> 'datetime',
		'period_end'			=> 'datetime',
		'failed_at'				=> 'datetime',
		'metadata'				=> 'array',
		'amount'				=> 'integer',
	];

	// Relationships (adjust table names if you renamed)
	public function user(){ return $this->belongsTo(\App\Models\User::class); }
	public function product(){ return $this->belongsTo(\App\Models\Product::class, 'product_id'); }
	public function productPrice(){ return $this->belongsTo(\App\Models\ProductPrice::class, 'product_price_id'); }
	public function entry(){ return $this->belongsTo(\App\Models\RacsterEntry::class, 'entry_id', 'id'); }

	// Scopes
	public function scopeSubscriptions(Builder $q){ return $q->whereNotNull('stripe_subscription_id'); }
	public function scopeOneOffs(Builder $q){ return $q->whereNull('stripe_subscription_id'); }
	public function scopeForMonth(Builder $q, string $ym){ return $q->where('covered_month', $ym); }

    // Get payment ID with stripe session ID
	public static function findIdBySessionId(string $sessionId): ?int {
		return static::where('stripe_checkout_session_id', $sessionId)->value('id');
	}

    // Get payment ID with stripe session ID
	public static function findByEntryID(string $entryId, ?int $userId = null): ?int {

		// Fallback to active user ID if not defined
		$userId = $userId ?? Auth::id();

		return static::query()
			->where('user_id', $userId)
			->where('entry_id', $entryId)
			->whereNull('date_id')
			->whereNotNull('stripe_invoice_id')
			->subscriptions()
			->where('status', 'paid')
			->whereNull('deleted_at')
			->value('id');

	}

	// Mark user payment cancelled / expired
	public function markCancelled($status = 'canceled'){

		if ($this->status === 'pending' and empty($this->stripe_invoice_id)){

			Log::channel('stripecancelled')->notice('Stripe payment '.$status, [
				'id' => $this->id,
				'session_id' => $this->stripe_checkout_session_id,
			]);

			if (!empty($this->entry_id) and !empty($this->date_id)){

				Log::channel('stripecancelled')->notice('Revoke attendance', [
					'user_id' => $this->user_id,
					'entry_id' => $this->entry_id,
					'date_id' => $this->date_id,
				]);

				// Mark user onhold transaction as deleted
				\App\Models\UserTransactions::deleteOnholdTransaction($this->user_id, $this->date_id, $this->stripe_checkout_session_id);

				// Check if client is attending or not
				$cid = DB::table('racster_entry_users')
					->where('creator_id', $this->user_id)
					->where('entry_id', $this->entry_id)
					->where('date_id', $this->date_id)
					->where('user_type', 'client')
					->where('user_id', $this->user_id)
					->whereNull('deleted_at')
					->value('id');

				if (!empty($cid)){

					// Get client quantity for the entry date
					$client_quantity = DB::table('racster_entry_users')
						->where('id', $cid)
						->whereNull('deleted_at')
						->value('user_quantity');

					// Remove clients from entry date
					DB::table('racster_entry_users')
						->where('id', $cid)
						->whereNull('deleted_at')
						->update([
							'deleted_at' => \Carbon\Carbon::now(),
						]);

					// Get entry date clients count for subtraction
					$client_count = DB::table('racster_entry_dates')
						->where('id', $this->date_id)
						->where('entry_id', $this->entry_id)
						->whereNull('deleted_at')
						->value('client_count');

					// Define updatable date data
					$datedata = [
						'client_count' => (!empty($client_count) ? ($client_count-(!empty($client_quantity) ? $client_quantity : 1)) : 0),
						'updated_at' => \Carbon\Carbon::now(),
					];

					// Make private event public without clients
					if (empty($client_count) or ($client_count-(!empty($client_quantity) ? $client_quantity : 1)) < 1){
						$datedata['private_entry'] = 0;
					}

					// Update client count in entry date row
					DB::table('racster_entry_dates')
						->where('id', $this->date_id)
						->where('entry_id', $this->entry_id)
						->whereNull('deleted_at')
						->update($datedata);

				}

			}

			$this->update([
				'status' => $status,
				'updated_at' => now(),
			]);

			Log::channel('stripecancelled')->notice('Stripe payment set '.$status, [
				'id' => $this->id,
				'session_id' => $this->stripe_checkout_session_id,
			]);

		}

	}

}
