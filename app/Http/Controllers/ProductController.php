<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {

		$this->perpage = 25; // Define products count on one page

		$this->product_rules = [ // Define products validation
			'name' => ['required','string','max:255'],
			'description' => ['nullable','string'],
			'currency' => ['required','string','max:10'],
			'default_amount' => ['required','nullable','integer','min:100'],
			'interval' => ['nullable','in:month,year'],
			'active' => ['sometimes','boolean'],
		];

	}

	/**
	 * Display stripe products list
	 */
    public function index(){

        $products = Product::with('prices')->paginate($this->perpage);

        return view('products.index', compact('products'));

    }

	/**
	 * Display stripe new product creation view
	 */
    public function create(){

        return view('products.create');

    }

	/**
	 * Save stripe product data
	 */
    public function store(Request $request){

        $data = $request->validate($this->product_rules);

        $product = Product::create($data + ['active' => $request->boolean('active', true)]);

        return redirect()->route('products.show', $product)->with('message', trans('stripe-products.stripe-product-saved-successfully'));

    }

	/**
	 * Show stripe product management view
	 */
    public function show(Product $product){

        $product->load('activePrices');

        return view('products.show', compact('product'));

    }

	/**
	 * Display stripe product default data management view
	 */
    public function edit(Product $product){

        return view('products.edit', compact('product'));

    }

	/**
	 * Save stripe product default data
	 */
    public function update(Request $request, Product $product){

        $data = $request->validate($this->product_rules);

        $product->update($data + ['active' => $request->boolean('active', true)]);

        return redirect()->route('products.show', $product)->with('message', trans('stripe-products.stripe-product-saved-successfully'));

    }

	/**
	 * Delete stripe product
	 */
    public function destroy(Product $product){

        $product->delete();

        return redirect()->route('products.index')->with('message', trans('stripe-products.stripe-product-deleted-successfully'));

    }

}
