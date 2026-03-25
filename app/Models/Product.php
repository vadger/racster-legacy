<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

	protected $table = 'stripe_products';
	protected $guarded = [];
	protected $casts = [
		'active' => 'boolean',
		'metadata' => 'array',
	];

	public function prices(){

		return $this->hasMany(ProductPrice::class);

	}

	public function activePrices()
	{

		return $this->hasMany(ProductPrice::class)->where('active', true);

	}

	public function scopeActive($q){

		return $q->where('active', true);

	}

	public function scopeOneOff($q){

		return $q->whereNull('interval');

	}

	public function scopeSubscription($q){

		return $q->whereNotNull('interval');

	}

	public function oneOffPrices(){

		return $this->hasMany(ProductPrice::class)
			->where('active', true)
			->whereNull('interval')
			->orderBy('created_at', 'asc');

	}

	public function oneSubsPrices(){

		return $this->hasMany(ProductPrice::class)
			->where('active', true)
			->whereNotNull('interval')
			->orderBy('created_at', 'asc');

	}	

}
