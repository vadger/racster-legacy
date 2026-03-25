<?php

namespace App\Models;

use DB;
use Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserTransactions extends Model
{

	use SoftDeletes;

	protected $table = 'racster_user_transactions';
	protected $guarded = [];
	protected $casts = [
		'transaction_amount' => 'decimal:2',
	];

	protected $perPage = 25;

	public const TYPES = ['added', 'used', 'onhold'];
	public const TYPE_ADDED = 'added';
	public const TYPE_USED = 'used';
	public const TYPE_ONHOLD = 'onhold';

	// Scopes
	public function scopeWithValidTypes($q){ return $q->whereIn('transaction_type', self::TYPES); }
	public function scopeWithTypes($q, array $types){ return $q->whereIn('transaction_type', $types); }
	public function scopeType($q, string $type){ return $q->where('transaction_type', $type); }
	public function scopeWithDates($q, array $dates){ return $q->whereIn('date_id', $dates); }
	public function scopeDate($q, string $date){ return $q->where('date_id', $date); }
	public function scopeOwnedBy($q, int $creatorId){ return $q->where('creator_id', $creatorId); }
	public function scopeForUser($q, int $userId){ return $q->where('user_id', $userId); }
	public function scopeForUsers($q, array $users){ return $q->whereIn('user_id', $users); }

	// Add new transaction for user
	public static function createTransaction(string $type, array $data): self {

		// Validate type
		if (!in_array($type, self::TYPES, true)){
			throw new InvalidArgumentException("Invalid transaction type: {$type}");
		}

		$amount = $data['amount'] ?? 1;
		$comment = $data['comment'] ?? null;
		$dateId = $data['date_id'] ?? null;
		$userId = $data['user_id'] ?? Auth::id();

		return static::create([
			'creator_id'			=> (Auth::id() ? Auth::id() : $userId),
			'transaction_type'		=> $type,
			'transaction_amount'	=> $amount,
			'transaction_comment'	=> $comment,
			'date_id'				=> $dateId,
			'user_id'				=> $userId,
		]);

	}

	// Update client transaction
	public static function updateTransaction(int $tid, array $data): int {

		// Add updated_at to data array
		$data['updated_at'] = \Carbon\Carbon::now();

		return static::whereKey($tid)
			->update($data);

	}

	// Get user transactions
	public static function paginatedList(int $userId, ?int $perPage = null){

		// Fallback to local page count
		$perPage = $perPage ?? (new static)->getPerPage();
		$table = (new static)->getTable();

		return static::query()
			->withValidTypes()
			->where("{$table}.user_id", $userId)
			->leftJoin('racster_entry_dates as dates', 'dates.id', '=', "{$table}.date_id")
			->select([
				"{$table}.*",
				'dates.entry_start',
			])
			->orderByDesc("{$table}.created_at")
			->orderByDesc("{$table}.id")
			->paginate($perPage);

	}

	// Get user transactions balance
	public static function getBalance(?int $userId = null): int {

		// Fallback to active uesr ID if not defined
		$userId = $userId ?? Auth::id();

		// Get user transactions balance
		$balance = static::query()
			->where('user_id', $userId)
			->whereIn('transaction_type', [self::TYPE_ADDED, self::TYPE_USED])
			->selectRaw("
				COALESCE(SUM(CASE WHEN transaction_type = ? THEN transaction_amount ELSE 0 END), 0)
				- COALESCE(SUM(CASE WHEN transaction_type = ? THEN transaction_amount ELSE 0 END), 0) AS balance
				", [self::TYPE_ADDED, self::TYPE_USED])
			->value('balance');

		return (int) round($balance ?: 0, 0);

	}

	// Get user transaction by ID
	public static function getUserTransaction(int $transactionId, string $type = 'added', int $userId, ?int $creatorId = null): ?self {

		// Validate type
		if (!in_array($type, self::TYPES, true)){
			throw new InvalidArgumentException("Invalid transaction type: {$type}");
		}

		if (!empty(config('racster.superadmin')) and in_array(Auth::user()->id, config('racster.superadmin'))){

			return static::query()
				->whereKey($transactionId)
				->forUser($userId)
				->first();

		}else{

			// Fallback to active user ID if not defined
			$creatorId = $creatorId ?? Auth::id();

			return static::query()
				->whereKey($transactionId)
				->ownedBy($creatorId)
				->type($type)
				->forUser($userId)
				->first();

		}

	}

	// Get user first transaction
	public static function getUserDateTransaction(array $types, int $dateId, ?int $userId = null): ?self {

		// Validate types
		if (!empty(array_diff($types, self::TYPES))){
			throw new InvalidArgumentException("Invalid transaction types: ".implode(';', $types));
		}

		// Fallback to active user ID if not defined
		$userId = $userId ?? Auth::id();

		return static::query()
			->withTypes($types)
			->date($dateId)
			->forUser($userId)
			->first();

	}

	// Mark transactions by date IDs as deleted
	public static function deleteTransactionsByDates(array $dates, array $types, ?array $users = null): int {

		if (!empty($users) and is_array($users)){

			return static::query()
				->withDates($dates)
				->withTypes($types)
				->ForUsers($users)
				->delete();

		}else{

			return static::query()
				->withDates($dates)
				->withTypes($types)
				->delete();

		}

	}	

	// Mark onhold transaction as deleted
	public static function deleteOnholdTransaction(int $userId, int $dateId, string $sessID = null): int {

		// Fallback to active uesr ID if not defined
		$userId = $userId ?? Auth::id();

		$latest = static::query()
			->ownedBy($userId)
			->withTypes([self::TYPE_ONHOLD, self::TYPE_USED])
			->forUser($userId)
			->where('date_id', $dateId)
			->where('stripe_sess_id', $sessID)
			->latest('created_at')
			->first();

		if ($latest) {

			$latest->delete();
			return 1;

		}

		return 0;

	}

}
