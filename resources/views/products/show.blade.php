@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-lg-12">

			@if (Auth::user()->hasRole('admin'))

				@if (count($errors) > 999) <div class="alert alert-danger main-alert"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
				@if(Session::has('notice')) <div class="alert alert-danger main-alert text-center"> {!! Session::get('notice') !!} </div> @endif
				@if(Session::has('message')) <div class="alert alert-success main-alert text-center"> {!! Session::get('message') !!} </div> @endif

				<div class="card shadow rounded">
					<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
						<span class="fw-bold">@lang('stripe-products.product-header'): {{ $product->name }}</span>
						<div class="text-center">
							<a href="{{ route('products.create') }}" class="btn btn-primary btn-sm mt-lg-0 mt-1">
								@lang('stripe-products.add-new-product')
							</a>
							<a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
								@lang('stripe-products.back-to-products')
							</a>
						</div>
					</div>
					<div class="card-body">

@if (!empty($product->description) || !empty($product->stripe_product_id))
						<div class="row">
							<div class="col-12 mb-4">
								<div class="alert alert-primary mb-0 small">
									<strong>@lang('stripe-products.stripe-product-id'):</strong> {{ $product->stripe_product_id ?? '— (created on demand)' }}
@if (!empty($product->description) && !empty($product->stripe_product_id))
									<hr class="my-2 text-primary" />
@endif
									{{ $product->description }}
								</div>
							</div>
						</div>
@endif

						<div class="row">
							<div class="col-12 col-md-6 mb-4">

								<div class="border border-primary rounded p-3 h-100">

									<div class="h6">@lang('stripe-products.create-or-reuse-stripe-price')</div>

									<form class="form-horizontal" role="form" method="POST" action="{{ route('products.prices.store', $product) }}" id="add-new-subscription-price">
										@csrf

										<div class="row my-3">
											<div class="col-12 col-lg-6">
												<input type="number" name="unit_amount" min="100" step="50" class="form-control" placeholder="@lang('stripe-products.field-amount-placeholder')" required />
											</div>
											<div class="col-12 col-lg-6 mt-2 mt-lg-0">
												<input type="hidden" name="interval" class="form-control" value="{{ $product->interval }}" required readonly />
												<input type="hidden" name="currency" class="form-control" value="{{ $product->currency }}" required readonly />
												<button class="btn btn-outline-info w-100">@lang('stripe-products.create-or-reuse-button')</button>
											</div>
										</div>

									</form>

									<div class="form-text mt-2">@lang('stripe-products.create-or-reuse-button-additional-info')</div>

								</div>

							</div>
							<div class="col-12 col-md-6 mb-4">

								<div class="border border-secondary rounded p-3 h-100">

									<div class="h6">@lang('stripe-products.one-off-quick-pay')</div>

									<form class="form-horizontal" role="form" method="POST" action="{{ route('checkout.oneoff', $product) }}" id="add-new-oneoff-price">
										@csrf

										<div class="row my-3">
											<div class="col-12 col-lg-6">
												<input type="number" name="amount" min="100" step="50" class="form-control" placeholder="@lang('stripe-products.field-amount-placeholder')" required />
											</div>
											<div class="col-12 col-lg-6 mt-2 mt-lg-0">
												<input type="hidden" name="currency" class="form-control" value="{{ $product->currency }}" required readonly />
												<button class="btn btn-outline-secondary w-100">@lang('stripe-products.pay-button')</button>
											</div>
										</div>

									</form>

									<div class="form-text mt-2">@lang('stripe-products.pay-button-additional-info')</div>

								</div>

							</div>
						</div>

						<div class="row">
							<div class="col-12">

							<div class="table-responsive">
								<table class="table table-sm align-middle">
									<thead class="table-light">
										<tr>
											<th>@lang('stripe-products.existing-stripe-price-column')</th>
											<th>@lang('stripe-products.amount-column')</th>
											<th>@lang('stripe-products.currency-column')</th>
											<th>@lang('stripe-products.interval-column')</th>
											<th class="text-end">&nbsp;</th>
										</tr>
									</thead>
									<tbody>
										@forelse ($product->activePrices as $price)
											<tr>
												<td class="text-nowrap">{{ $price->stripe_price_id ?? '—' }}</td>
												<td>{{ number_format($price->unit_amount/100, 2) }}</td>
												<td>{{ strtoupper($price->currency) }}</td>
												<td>@lang('stripe-products.interval-option-'.($price->interval ?? 'onetime'))</td>
												<td class="text-end text-nowrap">
													<div class="d-inline-flex gap-2">
														@if ($price->stripe_price_id && $price->interval)
															<form method="post" action="{{ route('checkout.subscribe', $product) }}">
																@csrf
																<input type="hidden" name="product_price_id" value="{{ $price->id }}">
																<button class="btn btn-sm btn-primary">@lang('stripe-products.subscribe-button')</button>
															</form>
														@endif
														<form method="post" action="{{ route('products.prices.destroy', [$product, $price]) }}" onsubmit="return confirm('@lang('stripe-products.sure-to-archive-price')')">
															@csrf @method('DELETE')
															<button class="btn btn-sm btn-outline-danger">@lang('stripe-products.archive-button')</button>
														</form>
													</div>
												</td>
											</tr>
										@empty
											<tr>
												<td colspan="5" class="text-secondary">@lang('stripe-products.no-stripe-prices-added')</td>
											</tr>
										@endforelse
									</tbody>
								</table>
							</div>

						</div>
					</div>

					</div>
					<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">

						<div class="text-start text-secondary">
							<form method="post" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('@lang('stripe-products.sure-to-delete-product')')">
								@csrf @method('DELETE')
								<button class="btn btn-danger">@lang('stripe-products.delete-product')</button>
							</form>
						</div>
						<div class="text-center">
							<a href="{{ route('products.edit', $product) }}" class="btn btn-secondary">@lang('stripe-products.manage-product-default-data')</a>
						</div>

					</div>
				</div>

			@endif

		</div>
	</div>

@endsection

@push("custom_scripts")

	<script type="text/javascript">
		$(document).ready(function ($) {
			$('.main-alert.alert-danger').delay(5000).fadeOut('slow');
			$('.main-alert.alert-success').not('.collapse').delay(3000).fadeOut('slow');
			$("form#add-new-subscription-price, form#add-new-oneoff-price").on("submit", function(e) {
				e.stopPropagation(); e.preventDefault();
				var _price = ($(this).find('input[name$="amount"]').val() / 100);
				if (confirm("@lang('stripe-products.sure-to-create-with-price') " + _price + " {{ config('racster.main-currency') }}?")) {
					this.submit();
					return true;
				} else {
					return false;
				}
			});
		});
	</script>

@endpush
