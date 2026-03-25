<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{

	protected $table = 'stripe_product_prices';
	protected $guarded = [];
	protected $casts = [
		'active' => 'bool',
		'is_custom' => 'bool',
	];

	public function product(){

		return $this->belongsTo(Product::class);

	}

	public static function makeHash(int $productId, int $unitAmount, string $currency, ?string $interval): string {

		return hash('sha256', implode(':', [$productId, $unitAmount, strtolower($currency), $interval ?? 'oneoff']));

	}

}
