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
						<span>@lang('stripe-products.stripe-products-header')</span>
						<div class="text-center">
							<a href="{{ route('products.create') }}" class="btn btn-primary btn-sm mt-lg-0 mt-1">
								@lang('stripe-products.add-new-product')
							</a>
						</div>
					</div>
					<div class="card-body">

						@if ($products->count())

							<div class="row mb-2">
								<div class="col-lg-12">
									<ul class="list-group list-group-flush">

										@foreach ($products as $product)
											<a href="{{ route('products.show', $product) }}" class="list-group-item list-group-item-action list-group-item-light d-flex flex-column">
												<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-1">
													<div class="d-flex justify-content-between align-items-center gap-1 me-md-auto">
														<i class="far fa{{ ((!empty($product->active) && $product->active == 1) ? '-check' : '') }}-square"></i>
														<strong>{{ $product->name }}</strong>
														<small class="ms-auto text-dark">({{ $product->activePrices()->count() }} prices)</small>
													</div>
													<ul class="list-group list-group-horizontal">
														<li class="list-group-item list-group-item-secondary px-2 py-1 small">
															@lang('stripe-products.defaults'):
														</li>
														<li class="list-group-item px-2 py-1 small">
															@if ($product->default_amount)
																{{ number_format($product->default_amount/100, 2) }}
															@else
																—
															@endif
															{{ config('racster.main-currency')}}
														</li>
														<li class="list-group-item list-group-item-primary px-2 py-1 small">
															<i class="fas fa-history"></i> @lang('stripe-products.interval-option-'.($product->interval ?? 'onetime'))
														</li>
													</ul>
												</div>
												<div class="mt-2 mt-md-0 text-body-secondary small">
													{{ \Illuminate\Support\Str::limit($product->description, 120) }}
												</div>
											</a>
										@endforeach

									</ul>
								</div>
							</div>

							<div class="form-group row mb-0 mt-3">
								<div class="col-12">
									<div class="def-pagi text-center">{{ $products->total() }} @lang("stripe-products.products") {{ $products->onEachSide(2)->links() }}</div>
								</div>
							</div>

						@else
							<div class="alert alert-info">@lang('stripe-products.no-products-found')</div>
						@endif

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
		});
	</script>

@endpush
