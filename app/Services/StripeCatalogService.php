<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use Stripe\StripeClient;

class StripeCatalogService
{

	private StripeClient $stripe;

	public function __construct(?StripeClient $stripe = null)
	{
		$this->stripe = $stripe ?: new StripeClient(env('STRIPE_SECRET'));
	}

	public function ensureStripeProduct(Product $product): string
	{

		if ($product->stripe_product_id){
			return $product->stripe_product_id;
		}

		$sp = $this->stripe->products->create(
			[
				'name' => $product->name,
				'active' => $product->active,
				'metadata' => ['laravel_product_id' => (string)$product->id],
			]
		);

		$product->update(['stripe_product_id' => $sp->id]);

		return $sp->id;

	}

	public function findOrCreateStripePrice(Product $product, int $amount, string $currency, ?string $interval): ProductPrice
	{

		$hash = ProductPrice::makeHash($product->id, $amount, $currency, $interval);

		$pp = ProductPrice::where('hash', $hash)->where('active', true)->first();
		if ($pp and $pp->stripe_price_id){
			return $pp;
		}

		$productId = $this->ensureStripeProduct($product);

		$payload = [
			'unit_amount'	=> $amount,
			'currency'		=> $currency,
			'product'		=> $productId,
			'metadata'		=> [
				'laravel_product_id'	=> (string) $product->id,
				'custom'				=> '1',
			],
		];

		if ($interval) {
			$payload['recurring'] = ['interval' => $interval];
		}

		$price = $this->stripe->prices->create($payload);

		return ProductPrice::updateOrCreate(
			['hash' => $hash],
			[
				'product_id'      => $product->id,
				'unit_amount'     => $amount,
				'currency'        => $currency,
				'interval'        => $interval,
				'is_custom'       => true,
				'active'          => true,
				'stripe_price_id' => $price->id,
			]
		);

	}

}
