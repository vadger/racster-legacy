@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-xl-10">

			@if (count($errors) > 999) <div class="alert alert-danger main-alert"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
			@if(Session::has('notice')) <div class="alert alert-danger main-alert text-center"> {!! Session::get('notice') !!} </div> @endif
			@if(Session::has('message')) <div class="alert alert-success main-alert text-center"> {!! Session::get('message') !!} </div> @endif

			<div class="card shadow rounded">
				<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
@if (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true)
					<span>@lang('stripe-products.user-subscriptions-header', ['username' => ($owner->first_name ?? $owner->name)])</span>
@else
					<span>@lang('stripe-products.my-subscriptions-header')</span>
@endif
				</div>
				<div class="card-body">

@if($subscriptions->isEmpty())

					<div class="row">
						<div class="col-12">
							@lang('stripe-products.no-subscriptions-found')
						</div>
					</div>

@else

					<div class="table-responsive">
						<table class="table table-sm table-hover align-middle">

							<thead>
								<tr>
									<th>@lang('stripe-products.subscription-column-status')</th>
									<th>@lang('stripe-products.subscription-column-endsat')</th>
									<th>@lang('stripe-products.subscription-column-trialends')</th>
									<th>&nbsp;</th>
								</tr>
							</thead>

							<tbody>
@foreach($subscriptions as $s)
								<tr>
									<td class="text-{{ ($s->stripe_status == 'canceled' ? 'secondary' : 'dark') }}"><small>{{ mb_ucfirst(trans('stripe-products.subscription-status-'.$s->stripe_status)) }}</small></td>
									<td class="text-{{ ($s->stripe_status == 'canceled' ? 'secondary' : 'dark') }}"><small>{{ optional($s->ends_at)?->toDateTimeString() ?? '—' }}</small></td>
									<td class="text-{{ (($s->stripe_status == 'canceled' || ($s->trial_ends_at && $s->trial_ends_at->isPast())) ? 'secondary' : 'dark') }}">
										<small>{{ $s->trial_ends_at ? ($s->trial_ends_at->isPast() ? trans('stripe-products.subscription-status-trial-ended') : $s->trial_ends_at->toDateTimeString()) : '' }}</small>
									</td>
									<td class="py-0 text-end text-{{ ($s->stripe_status == 'canceled' ? 'secondary' : 'dark') }}">
@if (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true)
										<a class="btn btn-sm btn-{{ ($s->stripe_status == 'canceled' ? 'outline-secondary' : 'primary') }}" href="{{ route('subscription.admin.show', ['subscription' => $s->id]) }}">
											@lang('stripe-products.view-subscription')
										</a>
@elseif($s->stripe_status != 'canceled')
										<a class="btn btn-sm btn-primary" href="{{ route('subscription.show', ['subscription' => $s->id]) }}">
											@lang('stripe-products.view-subscription')
										</a>
@endif
									</td>
								</tr>
@endforeach
							</tbody>

						</table>
					</div>

@endif

				</div>
			</div>

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
