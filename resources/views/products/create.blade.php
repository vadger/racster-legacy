@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-lg-12">

			@if (Auth::user()->hasRole('admin'))

				@if (count($errors) > 999) <div class="alert alert-danger main-alert"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
				@if(Session::has('notice')) <div class="alert alert-danger main-alert text-center"> {!! Session::get('notice') !!} </div> @endif
				@if(Session::has('message')) <div class="alert alert-success main-alert text-center"> {!! Session::get('message') !!} </div> @endif

				<form class="form-horizontal" role="form" method="POST" action="{{ route('products.store') }}">
					@csrf

					<div class="card shadow rounded">
						<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
							<span>@lang('stripe-products.manage-product-header')</span>
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

							<div class="row">
								<div class="col-12 col-lg-6">

									<div class="row mb-1">
										<label class="col-5 col-form-label fw-bold" for="name">
											@lang('stripe-products.field-product-title')*
										</label>
										<div class="col-7">
											<input class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" value="{{ (old('name') ? old('name') : '') }}" type="text" name="name" id="name" autocomplete="off" required />
										</div>
									</div>

									<div class="row mb-1">
										<div class="col-12">
											<textarea class="form-control{{ $errors->has('description') ? ' is-invalid' : '' }}" rows="3" name="description" id="description" placeholder="@lang('stripe-products.field-product-description-placeholder')">{{ (old('description') ? old('description') : '') }}</textarea>
										</div>
									</div>

								</div>
								<div class="col-12 col-lg-6">

									<div class="row mb-1">
										<label class="col-5 col-form-label fw-bold" for="currency">
											@lang('stripe-products.field-currency-title')*
										</label>
										<div class="col-7">
											<input class="form-control{{ $errors->has('currency') ? ' is-invalid' : '' }}" value="{{ (old('currency') ? old('currency') : strtolower(config('racster.main-currency'))) }}" type="text" name="currency" id="currency" autocomplete="off" required readonly />
										</div>
									</div>
									<div class="row mb-1">
										<label class="col-5 col-form-label fw-bold" for="default_amount">
											@lang('stripe-products.field-default-amount-title')*
										</label>
										<div class="col-7">
											<input class="form-control{{ $errors->has('default_amount') ? ' is-invalid' : '' }}" value="{{ (old('default_amount') ? old('default_amount') : '') }}" type="number" name="default_amount" id="default_amount" placeholder="@lang('stripe-products.field-default-amount-placeholder')" autocomplete="off" />
										</div>
									</div>
									<div class="row mb-1">
										<label class="col-5 col-form-label fw-bold" for="interval">
											@lang('stripe-products.field-default-interval-title')*
										</label>
										<div class="col-7">
											<select class="form-select{{ $errors->has('interval') ? ' is-invalid' : '' }}" name="interval" id="interval">
												<option value="" @selected(old('interval')==='')>@lang('stripe-products.interval-option-onetime')</option>
												<option value="month" @selected(old('interval')==='month')>@lang('stripe-products.interval-option-month')</option>
												<option value="year" @selected(old('interval')==='year')>@lang('stripe-products.interval-option-year')</option>
											</select>
										</div>
									</div>
									<div class="row mb-1">
										<label class="col-5 col-form-label fw-bold" for="active">
											@lang('stripe-products.field-status-title')*
										</label>
										<div class="col-7">
											<div class="form-check mt-1 p-0">
												<input type="hidden" name="active" value="0">
												<input class="form-check-input" type="checkbox" name="active" value="1" id="active"{{ ((!empty(old('active')) && old('active') == '1') ? ' checked' : '') }} />
												<label class="form-check-label small text-secondary" for="active">@lang('stripe-products.active')</label>
											</div>
										</div>
									</div>

								</div>
							</div>

						</div>
						<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">

							<div class="text-start text-secondary">
								&nbsp;
							</div>
							<div class="text-center">
								<button class="btn btn-primary">@lang('stripe-products.save-product')</button>
							</div>

						</div>
					</div>

				</form>

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
