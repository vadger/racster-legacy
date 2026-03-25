<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\StripeCatalogService;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{

	public function store(Request $request, Product $product, StripeCatalogService $catalog)
	{

		$data = $request->validate(
			[
				'unit_amount' => ['required','integer','min:100'],
				'currency'    => ['required','string','max:10'],
				'interval'    => ['nullable','in:month,year'],
			]
		);

		$pp = $catalog->findOrCreateStripePrice($product, $data['unit_amount'], $data['currency'], $data['interval'] ?? null);

		return back()->with('message', trans('stripe-products.stripe-price-created-successfully', ['stripeID' => $pp->stripe_price_id]));

	}

	public function destroy(Product $product, ProductPrice $price)
	{

		abort_unless($price->product_id === $product->id, 404);
		$price->update(['active' => false]);

		return back()->with('message', trans('stripe-products.stripe-product-price-successfully-archived-locally'));

	}
	
}
