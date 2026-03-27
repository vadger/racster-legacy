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
					<div class="text-center">
						<a href="{{ ((Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true) ? LaravelLocalization::localizeUrl('/users/subscriptions/'.$owner->id) : LaravelLocalization::localizeUrl('/subscriptions/')) }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
							@lang('stripe-products.back-to-subscriptions')
						</a>
					</div>
				</div>
				<div class="card-body">

@if (!$subscription)

					<div class="row">
						<div class="col-12">
							@lang('stripe-products.no-subscriptions-found')
						</div>
					</div>

@else

					<div class="row">
						<div class="col-12 mb-4">
							<div class="alert alert-primary mb-0 small text-center">

								<div class="row">
									<div class="col-12 col-md-6">
										@lang('stripe-products.subscription-status'): <strong>@lang('stripe-products.subscription-status-'.$subscription['stripe_status'])</strong>
									</div>
@if ($subscription['canceled'])
									<div class="col-12 col-md-6">
@if ($subscription['on_grace'])
										@lang('stripe-products.ending-on')
@else
										@lang('stripe-products.cancelled-at')
@endif
										<strong>{{ date('d.m.Y H:i', strtotime($subscription['ends_at'])) }}</strong>
									</div>
@elseif (!empty($subscription['trial_ends_at']) && strtotime($subscription['trial_ends_at']) > time())
									<div class="col-12 col-md-6">
										@lang('stripe-products.trial-ends-at') <strong>{{ date('d.m.Y H:i', strtotime($subscription['trial_ends_at'])) }}</strong>
									</div>
@elseif ($subscription['current_period_end'])
									<div class="col-12 col-md-6">
										@lang('stripe-products.renews-at') <strong>{{ date('d.m.Y H:i', strtotime($subscription['current_period_end'])) }}</strong>
									</div>
@endif
@if(!empty($subscription['paused']) && $subscription['paused'])
									<div class="col-12 col-md-6 align-middle small">
										<span class="fw-medium"><i class="fas fa-exclamation-triangle text-warning"></i> @lang('stripe-products.paused')</span>
									</div>
									<div class="col-12 col-md-6 small">
@if($subscription['pause_resumes_at'])
										@lang('stripe-products.resumes-on') <span class="fw-medium">{{ date('d.m.Y H:i', strtotime($subscription['pause_resumes_at'])) }}</span>
@endif
									</div>
@endif
								</div>

@if (!empty($entry))

								<hr class="my-2 text-primary" />

								<div class="row mt-2">
									<div class="col-12">
										<span class="fw-medium">@lang('stripe-products.related-entry'):</span>
@if (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true && !empty($payment))
										<a href="{{ LaravelLocalization::localizeUrl('/manage/entry/'.$payment->entry_id) }}" target="_blank" class="text-decoration-none">
@endif
											{{ $entry->entry_title }}
@if (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true && !empty($payment))
										</a>
@endif
										<small>({{ date('d.m.Y', strtotime($entry->minDate)) }} - {{ date('d.m.Y', strtotime($entry->maxDate)) }})</small>
									</div>
								</div>

@endif

							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-12 mb-3">

							<ul class="list-group">
@foreach($subscription['items'] as $i)

								<li class="list-group-item list-group-item-action list-group-item-light d-flex justify-content-between align-items-center rounded-0">
									<strong>
										{{ $i['product_name'] ?? $i['stripe_price'] }}
									</strong>
									<span>
@if (!is_null($i['effective_unit_amount'] ?? null))
	{{ number_format(($i['effective_unit_amount'] ?? 0) / 100, 2) }} {{ $i['currency'] }} / @lang('stripe-products.interval-option-'.($i['interval'] ?? 'period'))

	@if (!empty($i['discount_amount']) && !is_null($i['unit_amount']))
		<small class="text-muted text-decoration-line-through ms-1">
			{{ number_format($i['unit_amount'] / 100, 2) }} {{ $i['currency'] }}
		</small>

		@if (!empty($i['discount_percent']))
			<small class="text-success ms-1">
				(-{{ $i['discount_percent'] }}%)
			</small>
		@endif
	@endif
@elseif (!is_null($i['unit_amount']))
	{{ number_format($i['unit_amount'] / 100, 2) }} {{ $i['currency'] }} / @lang('stripe-products.interval-option-'.($i['interval'] ?? 'period'))
@else
	@lang('stripe-products.price-tbd')
@endif

@if (($i['quantity'] ?? 1) > 1)
	× {{ $i['quantity'] }}
@endif
									</span>
								</li>

@endforeach
							</ul>

						</div>
					</div>

@if ($subscription['stripe_status'] != 'canceled')

@if (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true)

@if (!empty($subscription['trial_ends_at']) && strtotime($subscription['trial_ends_at']) > time())

					<div class="row mt-4">
						<div class="col-12">
							<div class="alert alert-warning rounded p-3 text-center">
								<div class="row mb-0 align-items-center">
									<div class="col-12 col-md-6 h6 mb-md-0">@lang('stripe-products.change-subscription-trial-end')</div>
									<div class="col-12 col-md-6">
										<form method="POST" action="{{ route('subscription.admin.trial', ['subscription' => $subscription['id']]) }}">
											@csrf

											<div class="row mb-0">
												<div class="col-12 col-sm-7">
													<input class="form-control{{ $errors->has('trial_end_date') ? ' is-invalid' : '' }}" value="{{ old('trial_end_date', !empty($subscription['trial_ends_at']) ? date('d.m.Y', strtotime($subscription['trial_ends_at'])) : '') }}" placeholder="@lang('stripe-products.change-trial-date-placeholder')" type="text" name="trial_end_date" id="trial_end_date" autocomplete="off" />
												</div>
												<div class="col-12 col-sm-5 mt-1 mt-sm-0">
													<button class="btn btn-warning w-100">@lang('stripe-products.change-trial-button')</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>

@endif

					<div class="row mt-4">
						<div class="col-12 col-md-6 mb-2">

							<div class="border border-secondary rounded p-3 h-100">

								<div class="h6">@lang('stripe-products.change-subscription-stripe-price-or-quantity')</div>

								<form method="POST" action="{{ route('subscription.admin.swap', ['subscription' => $subscription['id']]) }}">
									@csrf

									<div class="row my-3">
										<div class="col-12 col-sm-7">
											<select name="price_id" class="form-select">
@foreach($availablePrices as $p)
												<option value="{{ $p['stripe_price'] }}">{{ $p['label'] }}</option>
@endforeach
											</select>
										</div>
										<div class="col-12 col-sm-5 mt-1 mt-sm-0">
											<button class="btn btn-outline-info w-100">@lang('stripe-products.change-price-button')</button>
										</div>
									</div>

								</form>

								<form method="POST" action="{{ route('subscription.admin.quantity', ['subscription' => $subscription['id']]) }}">
									@csrf

									<div class="row my-3">
										<div class="col-4">
											<input class="form-control" type="number" id="quantity" name="quantity" min="1" value="{{ $subscription['items'][0]['quantity'] ?? 1 }}">
										</div>
										<div class="col-8">
											<button class="btn btn-outline-secondary w-100">@lang('stripe-products.change-quantity-button')</button>
										</div>
									</div>

								</form>

							</div>

						</div>
						<div class="col-12 col-md-6 mb-2">

							<div class="border border-secondary rounded p-3 h-100">

								<div class="h6">@lang('stripe-products.pause-or-restart-stripe-subscription')</div>

@if (!empty($subscription['paused']) && $subscription['paused'])

								<form method="POST" action="{{ route('subscription.admin.restart', ['subscription' => $subscription['id']]) }}">
									@csrf

									<div class="row my-3">
										<div class="col-12 text-center">
											<button class="btn btn-outline-primary">@lang('stripe-products.restart-from-pause-button')</button>
										</div>
									</div>

								</form>

@else

								<form method="POST" action="{{ route('subscription.admin.pause', ['subscription' => $subscription['id']]) }}">
									@csrf

									<div class="row my-3">
										<div class="col-12 col-sm-6">
											<input class="form-control{{ $errors->has('resume_date') ? ' is-invalid' : '' }}" value="{{ (old('resume_date') ? old('resume_date') : '') }}" placeholder="@lang('stripe-products.start-pause-date-placeholder')" type="text" name="resume_date" id="resume_date" autocomplete="off" />
											<input type="hidden" name="behavior" class="form-control" value="void" required readonly />
										</div>
										<div class="col-12 col-sm-6 mt-1 mt-sm-0">
											<button class="btn btn-outline-info w-100">
												@lang('stripe-products.start-pause-button')
											</button>
										</div>
									</div>

								</form>

								<div class="form-text mt-2">@lang('stripe-products.pause-or-restart-stripe-subscription-additional-info')</div>

@endif

							</div>

						</div>
					</div>

@endif

@endif


@endif

				</div>
				<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">

@if ($subscription['stripe_status'] != 'canceled')

					<div class="d-flex flex-column flex-sm-row align-items-center gap-1 me-lg-auto">

						<form method="POST" action="{{ (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true) ? route('subscription.admin.cancel', ['subscription' => $subscription['id']]) : route('subscription.cancel', ['subscription' => $subscription['id']]) }}">
							@csrf
							<button class="btn btn-sm btn-outline-secondary">@lang('stripe-products.cancel-at-period-end-button')</button>
						</form>

@if (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true)

						<form method="POST" action="{{ route('subscription.admin.cancel_now', ['subscription' => $subscription['id']]) }}">
							@csrf
							<button class="btn btn-sm btn-outline-secondary">@lang('stripe-products.cancel-immediately-button')</button>
						</form>

@if (!empty($has_discount))

						<form method="POST" action="{{ (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true) ? route('subscription.admin.remove_discount', ['subscription' => $subscription['id']]) : route('subscription.remove_discount', ['subscription' => $subscription['id']]) }}">
							@csrf
							<button class="btn btn-sm btn-outline-info" onclick="return confirm('@lang('stripe-products.remove-subscription-discount-confirm')')">
								@lang('stripe-products.remove-subscription-discount-button')
							</button>
						</form>

@endif

@endif

					</div>

@if (Auth::user()->hasRole('admin') && !empty($adminview) && $adminview === true && $subscription['on_grace'])

					<div class="d-flex gap-1 ms-lg-auto">

						<form method="POST" action="{{ route('subscription.admin.resume', ['subscription' => $subscription['id']]) }}">
							@csrf
							<button class="btn btn-primary">@lang('stripe-products.resume-cancelled-button')</button>
						</form>

					</div>

@endif

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
			$('.main-alert.alert-success').not('.collapse').delay(5000).fadeOut('slow');

			// Define datepicker locals
			const DATEPICKER_REGIONALS = {
				closeText: '@lang('datepicker.close-text')',
				prevText: '@lang('datepicker.prev-text')',
				nextText: '@lang('datepicker.next-text')',
				currentText: '@lang('datepicker.current-text')',
				monthNames: [
					'@lang('datepicker.month-01')','@lang('datepicker.month-02')','@lang('datepicker.month-03')','@lang('datepicker.month-04')','@lang('datepicker.month-05')','@lang('datepicker.month-06')',
					'@lang('datepicker.month-07')','@lang('datepicker.month-08')','@lang('datepicker.month-09')','@lang('datepicker.month-10')','@lang('datepicker.month-11')','@lang('datepicker.month-12')'
				],
				monthNamesShort: [
					'@lang('datepicker.month-01-short')','@lang('datepicker.month-02-short')','@lang('datepicker.month-03-short')','@lang('datepicker.month-04-short')','@lang('datepicker.month-05-short')','@lang('datepicker.month-06-short')',
					'@lang('datepicker.month-07-short')','@lang('datepicker.month-08-short')','@lang('datepicker.month-09-short')','@lang('datepicker.month-10-short')','@lang('datepicker.month-11-short')','@lang('datepicker.month-12-short')'
				],
				dayNames: ['@lang('datepicker.day-01')','@lang('datepicker.day-02')','@lang('datepicker.day-03')','@lang('datepicker.day-04')','@lang('datepicker.day-05')','@lang('datepicker.day-06')','@lang('datepicker.day-07')'],
				dayNamesShort: ['@lang('datepicker.day-01-short')','@lang('datepicker.day-02-short')','@lang('datepicker.day-03-short')','@lang('datepicker.day-04-short')','@lang('datepicker.day-05-short')','@lang('datepicker.day-06-short')','@lang('datepicker.day-07-short')'],
				dayNamesMin: ['@lang('datepicker.day-01-min')','@lang('datepicker.day-02-min')','@lang('datepicker.day-03-min')','@lang('datepicker.day-04-min')','@lang('datepicker.day-05-min')','@lang('datepicker.day-06-min')','@lang('datepicker.day-07-min')'],
				weekHeader: '@lang('datepicker.week-head')',
				dateFormat: 'dd.mm.yy',
				firstDay: 1,
				isRTL: false,
				showMonthAfterYear: false,
				yearSuffix: ''
			};

			$.datepicker.setDefaults(DATEPICKER_REGIONALS);

			var $field = $('#resume_date');
			if ($field.length) {
				$field.datepicker({
					dateFormat: 'dd.mm.yy',
					minDate: 0,
					beforeShow: function (input, inst) {
						setTimeout(function () {
							inst.dpDiv.css('z-index', 7);
						}, 0);
					}
				});
			}

			var $trialField = $('#trial_end_date');
			if ($trialField.length) {
				$trialField.datepicker({
					dateFormat: 'dd.mm.yy',
					minDate: 0,
					beforeShow: function (input, inst) {
						setTimeout(function () {
							inst.dpDiv.css('z-index', 7);
						}, 0);
					}
				});
			}

		});
	</script>

@endpush
