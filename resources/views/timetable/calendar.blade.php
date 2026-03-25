@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-lg-12">

			@if (count($errors) > 999) <div class="alert alert-danger main-alert"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
			@if(Session::has('notice')) <div class="alert alert-danger main-alert text-center"> {!! Session::get('notice') !!} </div> @endif
			@if(Session::has('message')) <div class="alert alert-success main-alert text-center"> {!! Session::get('message') !!} </div> @endif

			<div class="row">
				<div class="col-12">
					<h3>@lang('racster.timetable-main-greeting', ['name' => (Auth::user()->first_name ?? Auth::user()->name)])</h3>
				</div>
			</div>

			<div class="card shadow rounded">
				<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
					<span class="d-flex align-items-center gap-1 text-primary">
@if (Auth::user()->hasRole('coach'))
						&nbsp;
@else
						<strong>@lang('racster.timetable-main-header', ['name' => (Auth::user()->first_name ?? Auth::user()->name)])</strong>
@endif
@if (!empty(Auth::user()->active_entries))
						<small class="text-secondary fst-italic">- @lang('racster.hidden-active-entries-'.Auth::user()->active_entries)</small>
@endif
					</span>
					<div class="text-center">
@if (Auth::user()->hasRole('manager'))
						<a href="{{ LaravelLocalization::localizeUrl('/manage/entry') }}" class="btn btn-primary btn-sm mt-lg-0 mt-1">
							@lang('racster.add-new-entry')
						</a>
@endif
@if (!in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))
						<a href="#" class="btn btn-sm btn-outline-secondary mt-lg-0 mt-1" data-bs-toggle="modal" data-bs-target="#manage-settings">
							<i class="fas fa-cog"></i>
						</a>
@endif
						<a href="{{ LaravelLocalization::localizeUrl('/view/week') }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
							@lang('racster.timetable-view-week')
						</a>
						<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['nowd']) }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
							@lang('racster.show-today')
						</a>
					</div>
				</div>
				<div class="card-body p-0">

@php $weekcnt = 1; $newm = 0; @endphp

@php

	// Define booking limit
	$booking_limit = now()->startOfWeek()->addWeek()->addDays(config('racster.prebooking-limit'));

	// Define minimum attendance periods
	$minperiods = [];
	if (!empty($assets['bytype']['entry-minperiod'])){
		foreach ($assets['bytype']['entry-minperiod'] as $minperiod){
			$minperiods[$minperiod->parent] = $minperiod->title;
		}
	}

@endphp

					<div class="caltable">
						<table border="0" cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th>&nbsp;</th>
									<th colspan="7">
										<div class="d-flex justify-content-between align-items-center">
											<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['divy']) }}" class="btn btn-sm btn-link text-dark mx-md-3 mx-1 px-md-5 px-2 py-0">
												<i class="fa fa-angle-double-left" aria-hidden="true"></i>
											</a>
											<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['prev']) }}" class="btn btn-sm btn-link text-dark mx-md-3 mx-1 px-md-5 px-2 py-0">
												<i class="fa fa-angle-left d-inline-block mx-1" aria-hidden="true"></i>
											</a>
											<span class="flex-grow-1">
												{{ trans('racster.'.date('n', $viewtime['topd']).'-month').' '.date('Y', $viewtime['topd']) }}
											</span>
											<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['next']) }}" class="btn btn-sm btn-link text-dark mx-md-3 mx-1 px-md-5 px-2 py-0">
												<i class="fa fa-angle-right d-inline-block mx-1" aria-hidden="true"></i>
											</a>
											<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['addy']) }}" class="btn btn-sm btn-link text-dark mx-md-3 mx-1 px-md-5 px-2 py-0">
												<i class="fa fa-angle-double-right" aria-hidden="true"></i>
											</a>
										</div>
									</th>
								</tr>
								<tr>
									<th class="small">&nbsp;</th>
									<th class="small">@lang('racster.monday-short')</th>
									<th class="small">@lang('racster.tuesday-short')</th>
									<th class="small">@lang('racster.wednesday-short')</th>
									<th class="small">@lang('racster.thursday-short')</th>
									<th class="small">@lang('racster.friday-short')</th>
									<th class="small">@lang('racster.saturday-short')</th>
									<th class="small">@lang('racster.sunday-short')</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>
										<small>{{ date('W', $viewtime['topd']) }}</small>
									</td>

@while ($viewtime['dcnt']++ < date('t', $viewtime['endd']))

@if ($viewtime['dcnt'] < 1)

									<td class="elseday {{ (in_array($weekcnt, array(6, 7)) ? 'weekend' : 'workday') }}" data-date="{{ date('t', $viewtime['prev'])+$viewtime['dcnt'].'-'.date('n-Y', $viewtime['prev']) }}">
										<small class="d-block px-1 text-end">{{ date('t', $viewtime['prev'])+$viewtime['dcnt'] }}</small>

@if (!empty($entries) && array_key_exists(date('Y-n', $viewtime['prev']).'-'.(date('t', $viewtime['prev'])+$viewtime['dcnt']), $entries))
@foreach ($entries[date('Y-n', $viewtime['prev']).'-'.(date('t', $viewtime['prev'])+$viewtime['dcnt'])] as $entry)

										<div class="date-entry{{ $entry->private_entry == 1 ? ' private-entry' : '' }}{{ $entry->recurring_entry == 1 ? ' recurring-entry' : '' }}{{ !empty($entry->active_user_row) ? ' attended-entry'.(!empty($entry->onhold) ? ' onhold' : '') : '' }}" data-eid="{{ $entry->id }}" data-bs-toggle="modal" data-bs-target="#show-entry-data" tabindex="0">
@if (!empty($assets) && !empty($assets['byid']) && array_key_exists($entry->entry_type, $assets['byid']))
											<div class="wt-event-title d-none d-lg-block fw-bold text-dark">
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]))
												{{ $assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
												{{ $assets['byid'][$entry->entry_type]->title }}
@endif
											</div>
@endif
											<div class="wt-event-meta d-flex flex-wrap flex-column flex-lg-row align-items-center justify-content-center justify-content-lg-between">

												<span class="text-dark">
													<i class="d-none d-xl-inline-block fas fa-clock me-0"></i>
													{{ date('H:i', strtotime($entry->entry_start)) }}
													<span class="d-none d-lg-inline-block ms-0">
														({{ round(((strtotime($entry->entry_ending)-strtotime($entry->entry_start))/(60*60)), 1) }}@lang('racster.hour-short'))
													</span>
												</span>

												<span class="text-secondary">
													<i class="d-none d-xl-inline-block fas fa-users me-1"></i>
													{{ $entry->client_count }}/{{ $entry->client_limit }}
												</span>

@if (!in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))

@php
    $start = \Carbon\Carbon::parse($entry->entry_start);
@endphp

@if (empty($entry->active_user_row) && $start->isFuture() &&
	($start->lessThan($booking_limit) || in_array($entry->entry_type, config('racster.unlimited-prebooking-limit'))) &&
	(strtotime('-'.(!empty($minperiods[$entry->entry_type]) ? (int)$minperiods[$entry->entry_type] : config('racster.attending-min-period')).' minutes', strtotime($entry->entry_start)) >= time()) &&
	($entry->client_limit - $entry->client_count)
)
@php
	$entry_price = (!empty($entry->entry_price) ? round($entry->entry_price, 0) : config('racster.default-price'));
	if (!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry->extra_price != 1){
		$entry_price = $entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0);
	}
@endphp
												<div class="wt-event-attend d-none d-lg-block">
													@lang('racster.attend') {{ $entry_price.config('racster.transaction-token') }}
												</div>
@endif

@endif

											</div>

										</div>

@endforeach
@endif

									</td>

@else

									<td class="{{ ((date('d-m-Y', $viewtime['nowd']) == ($viewtime['dcnt'] < 10 ? '0' : '').$viewtime['dcnt'].'-'.date('m-Y', $viewtime['topd'])) ? 'today' : (in_array($weekcnt, array(6, 7)) ? 'weekend' : 'workday')) }}" data-date="{{ $viewtime['dcnt'].'-'.date('n-Y', $viewtime['topd']) }}">
										<small class="d-block px-1 text-end">{{ $viewtime['dcnt'] }}</small>

@if (!empty($entries) && array_key_exists(date('Y-n', $viewtime['topd']).'-'.$viewtime['dcnt'], $entries))
@foreach ($entries[date('Y-n', $viewtime['topd']).'-'.$viewtime['dcnt']] as $entry)

										<div class="date-entry{{ $entry->private_entry == 1 ? ' private-entry' : '' }}{{ $entry->recurring_entry == 1 ? ' recurring-entry' : '' }}{{ !empty($entry->active_user_row) ? ' attended-entry'.(!empty($entry->onhold) ? ' onhold' : '') : '' }}" data-eid="{{ $entry->id }}" data-bs-toggle="modal" data-bs-target="#show-entry-data" tabindex="0">
@if (!empty($assets) && !empty($assets['byid']) && array_key_exists($entry->entry_type, $assets['byid']))
											<div class="wt-event-title d-none d-lg-block fw-bold text-dark">
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]))
												{{ $assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
												{{ $assets['byid'][$entry->entry_type]->title }}
@endif
											</div>
@endif
											<div class="wt-event-meta d-flex flex-wrap flex-column flex-lg-row align-items-center justify-content-center justify-content-lg-between">

												<span class="text-dark">
													<i class="d-none d-xl-inline-block fas fa-clock me-0"></i>
													{{ date('H:i', strtotime($entry->entry_start)) }}
													<span class="d-none d-lg-inline-block ms-0">
														({{ round(((strtotime($entry->entry_ending)-strtotime($entry->entry_start))/(60*60)), 1) }}@lang('racster.hour-short'))
													</span>
												</span>

												<span class="text-secondary">
													<i class="d-none d-xl-inline-block fas fa-users me-1"></i>
													{{ $entry->client_count }}/{{ $entry->client_limit }}
												</span>

@if (!in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))

@php
    $start = \Carbon\Carbon::parse($entry->entry_start);
@endphp

@if (empty($entry->active_user_row) && $start->isFuture() &&
	($start->lessThan($booking_limit) || in_array($entry->entry_type, config('racster.unlimited-prebooking-limit'))) &&
	(strtotime('-'.(!empty($minperiods[$entry->entry_type]) ? (int)$minperiods[$entry->entry_type] : config('racster.attending-min-period')).' minutes', strtotime($entry->entry_start)) >= time()) &&
	($entry->client_limit - $entry->client_count)
)
@php
	$entry_price = (!empty($entry->entry_price) ? round($entry->entry_price, 0) : config('racster.default-price'));
	if (!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry->extra_price != 1){
		$entry_price = $entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0);
	}
@endphp
												<div class="wt-event-attend d-none d-lg-block">
													@lang('racster.attend') {{ $entry_price.config('racster.transaction-token') }}
												</div>
@endif

@endif

											</div>

										</div>

@endforeach
@endif

									</td>

@if ($weekcnt == 7)
								</tr>
@if ($viewtime['dcnt'] != date('t', $viewtime['endd']))
								<tr>
									<td>
										<small>{{ date('W', strtotime(date('Y-m', $viewtime['topd']).'-'.($viewtime['dcnt']+1))) }}</small>
									</td>
@endif
@php $weekcnt = 0; @endphp
@endif

@endif

@php $weekcnt++; @endphp
@endwhile

@if ($weekcnt > 1 && $weekcnt <= 7)
@for ($wnr=$weekcnt;$wnr<=7;$wnr++)

									<td class="elseday {{ (in_array($wnr, array(6, 7)) ? 'weekend' : 'workday') }}" data-date="{{ $newm++.'-'.date('n-Y', $viewtime['next']) }}">
										<small class="d-block px-1 text-end">{{ $newm }}</small>

@if (!empty($entries) && array_key_exists(date('Y-n', $viewtime['next']).'-'.$newm, $entries))
@foreach ($entries[date('Y-n', $viewtime['next']).'-'.$newm] as $entry)

										<div class="date-entry{{ $entry->private_entry == 1 ? ' private-entry' : '' }}{{ $entry->recurring_entry == 1 ? ' recurring-entry' : '' }}{{ !empty($entry->active_user_row) ? ' attended-entry'.(!empty($entry->onhold) ? ' onhold' : '') : '' }}" data-eid="{{ $entry->id }}" data-bs-toggle="modal" data-bs-target="#show-entry-data" tabindex="0">
@if (!empty($assets) && !empty($assets['byid']) && array_key_exists($entry->entry_type, $assets['byid']))
											<div class="wt-event-title d-none d-lg-block fw-bold text-dark">
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]))
												{{ $assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
												{{ $assets['byid'][$entry->entry_type]->title }}
@endif
											</div>
@endif
											<div class="wt-event-meta d-flex flex-wrap flex-column flex-lg-row align-items-center justify-content-center justify-content-lg-between">

												<span class="text-dark">
													<i class="d-none d-xl-inline-block fas fa-clock me-0"></i>
													{{ date('H:i', strtotime($entry->entry_start)) }}
													<span class="d-none d-lg-inline-block ms-0">
														({{ round(((strtotime($entry->entry_ending)-strtotime($entry->entry_start))/(60*60)), 1) }}@lang('racster.hour-short'))
													</span>
												</span>

												<span class="text-secondary">
													<i class="d-none d-xl-inline-block fas fa-users me-1"></i>
													{{ $entry->client_count }}/{{ $entry->client_limit }}
												</span>

@if (!in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))

@php
    $start = \Carbon\Carbon::parse($entry->entry_start);
@endphp

@if (empty($entry->active_user_row) && $start->isFuture() &&
	($start->lessThan($booking_limit) || in_array($entry->entry_type, config('racster.unlimited-prebooking-limit'))) &&
	(strtotime('-'.(!empty($minperiods[$entry->entry_type]) ? (int)$minperiods[$entry->entry_type] : config('racster.attending-min-period')).' minutes', strtotime($entry->entry_start)) >= time()) &&
	($entry->client_limit - $entry->client_count)
)
@php
	$entry_price = (!empty($entry->entry_price) ? round($entry->entry_price, 0) : config('racster.default-price'));
	if (!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $entry->extra_price != 1){
		$entry_price = $entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0);
	}
@endphp
												<div class="wt-event-attend d-none d-lg-block">
													@lang('racster.attend') {{ $entry_price.config('racster.transaction-token') }}
												</div>
@endif

@endif

											</div>

										</div>

@endforeach
@endif

									</td>

@endfor
@endif

								</tr>
							</tbody>
						</table>
					</div>

				</div>
@if (!in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))
				<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">
					<div class="text-start"></div>
					<div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-1 my-1 my-md-2 my-lg-0">
@if (!empty($assets) && !empty($assets['bytype']['entry-location']))
						<select class="form-select form-select-sm py-1" name="filterby_location" id="filterby_location">
							<option value="">@lang('racster.filter-select-location')</option>
@foreach ($assets['bytype']['entry-location'] as $location)
							<option value="{{ $location->id }}"{{ ((Session::has('timetable-filter-location') && Session::get('timetable-filter-location') == $location->id) ? ' selected' : '') }}>
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$location->id][LaravelLocalization::getCurrentLocale()]))
								{{ $assets['langs'][$location->id][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
								{{ $location->title }}
@endif
							</option>
@endforeach
						</select>
@endif
@if (!empty($coaches))
						<select class="form-select form-select-sm py-1" name="filterby_coach" id="filterby_coach">
							<option value="">@lang('racster.filter-select-coach')</option>
@foreach ($coaches as $cid => $coach)
							<option value="{{ $cid }}"{{ ((Session::has('timetable-filter-coach') && Session::get('timetable-filter-coach') == $cid) ? ' selected' : '') }}>
								{{ $coach }}
							</option>
@endforeach
						</select>
@endif
@if (!empty($assets) && !empty($assets['bytype']['entry-type']))
						<select class="form-select form-select-sm py-1" name="filterby_etype" id="filterby_etype">
							<option value="">@lang('racster.filter-select-entry-type')</option>
@foreach ($assets['bytype']['entry-type'] as $type)
							<option value="{{ $type->id }}"{{ ((Session::has('timetable-filter-etype') && Session::get('timetable-filter-etype') == $type->id) ? ' selected' : '') }}>
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$type->id][LaravelLocalization::getCurrentLocale()]))
								{{ $assets['langs'][$type->id][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
								{{ $type->title }}
@endif
							</option>
@endforeach
						</select>
@endif
						<form class="d-inline-block" method="POST" action="{{ LaravelLocalization::localizeUrl('/timetable') }}">
							{{ csrf_field() }}
							<button name="clear-filters" id="clear-filters" class="btn btn-sm btn-secondary text-nowrap">
								@lang('racster.clear-filters')
							</button>
						</form>
					</div>
				</div>
@endif
			</div>

		</div>
	</div>

@include('modals.acquireTimetableEntry')
@include('timetable.filterBy')

@endsection

@push("custom_scripts")
	<script src="{{ asset('js/jquery.touchSwipe-1.6.18.min.js') }}"></script>
	<script type="text/javascript">
		$(document).ready(function ($) {
			$('.main-alert.alert-danger').delay(5000).fadeOut('slow');
			$('.main-alert.alert-success').not('.collapse').delay(3000).fadeOut('slow');
		});
		$(".caltable").swipe({
			preventDefaultEvents: false,
			threshold: 50,
			swipe:function(event, direction) {
				if (direction === "left"){
					location.href = '{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['next']) }}';
					e.stopPropagation();
				} else if(direction === "right"){
					location.href = '{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['prev']) }}';
					e.stopPropagation();
				}
			}
		});
	</script>
@endpush
