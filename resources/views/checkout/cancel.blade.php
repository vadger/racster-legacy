@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-lg-12">

			@if (count($errors) > 999) <div class="alert alert-danger main-alert"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
			@if(Session::has('notice')) <div class="alert alert-danger main-alert text-center"> {!! Session::get('notice') !!} </div> @endif
			@if(Session::has('message')) <div class="alert alert-success main-alert text-center"> {!! Session::get('message') !!} </div> @endif

			<div class="card shadow rounded">
				<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
					<span>@lang('stripe-products.checkout-payment-cancelled-main-header')</span>
					<div class="text-center">
						<a href="{{ LaravelLocalization::localizeUrl('/timetable') }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
							@lang('racster.back-to-timetable')
						</a>
					</div>
				</div>
				<div class="card-body">

					<div class="h4 mt-4 text-center">@lang('stripe-products.your-payment-was-successfully-cancelled')</div>
					<p class="text-body-secondary mb-3 text-center">@lang('stripe-products.your-payment-was-successfully-cancelled-additional-info')</p>

				</div>
			</div>

		</div>
	</div>

@endsection
