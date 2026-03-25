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
						<a href="{{ LaravelLocalization::localizeUrl('/view/calendar') }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
							@lang('racster.timetable-view-calendar')
						</a>
						<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['nowd']) }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
							@lang('racster.show-today')
						</a>
					</div>
				</div>
				<div class="card-body p-0">

@php $weekcnt = 1; $newm = 1; @endphp

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

					<div class="row weektable d-lg-none">
						<div class="col-12 mt-1">
							<div class="d-flex justify-content-between align-items-center week-col-title">
								<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['prevw']) }}" class="btn btn-sm btn-link text-dark mx-md-3 mx-1 px-md-5 px-2 py-0">
									<i class="fa fa-angle-left d-inline-block mx-1" aria-hidden="true"></i>
								</a>
								<span class="flex-grow-1">
									{{ intval(date('W', $viewtime['topw'])) }}. @lang('racster.week')
								</span>
								<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['nextw']) }}" class="btn btn-sm btn-link text-dark mx-md-3 mx-1 px-md-5 px-2 py-0">
									<i class="fa fa-angle-right d-inline-block mx-1" aria-hidden="true"></i>
								</a>
							</div>
						</div>
						<div class="col-12">
							<div class="d-flex flex-column flex-lg-row justify-content-evenly gap-1 week-col-dates">

@for ($dcnt=0;$dcnt<7;$dcnt++)

								<div class="w-100">

									<span class="d-none d-lg-block date-col-title">
										@lang('racster.'.strtolower(date('l', strtotime('+'.$dcnt.'days', $viewtime['topw']))).'-short') - {{ date('d.m', strtotime('+'.$dcnt.'days', $viewtime['topw'])) }}
									</span>

									<span class="d-block d-lg-none date-col-title" data-bs-target="#date-{{ date('dmY', strtotime('+'.$dcnt.'days', $viewtime['topw'])) }}">
										@lang('racster.'.strtolower(date('l', strtotime('+'.$dcnt.'days', $viewtime['topw']))).'-short') - {{ date('d.m', strtotime('+'.$dcnt.'days', $viewtime['topw'])) }}
										<i class="fas fa-chevron-down"></i>
									</span>

									<div class="collapse d-lg-block date-col-data" id="date-{{ date('dmY', strtotime('+'.$dcnt.'days', $viewtime['topw'])) }}">
@if (!empty(config('racster.timetable-range')))
@foreach (range(strtotime(config('racster.timetable-range.start').':00', $viewtime['topw']), strtotime(config('racster.timetable-range.end').':00', $viewtime['topw']), (config('racster.timetable-range.table')*60)) as $tabletime)
										<div class="row">
											<div class="col-12">
												<small class="d-none text-danger">{{ date('H:i', $tabletime) }}</small>
@php
	$tablehour=$tabletime;
@endphp
@while($tablehour<($tabletime+(config('racster.timetable-range.table')*60)))
@if (!empty($entries) && array_key_exists(date('Y-n-j-H-i', strtotime('+'.$dcnt.'days '.date('H:i', $tablehour), $viewtime['topw'])), $entries))

												<div class="p-1 date-col-time" data-date="{{ date('j-n-Y', strtotime('+'.$dcnt.'days', $viewtime['topw'])) }}" data-time="{{ date('H:i', $tablehour) }}">
													<small>{{ date('H:i', $tablehour) }}</small>

@foreach ($entries[date('Y-n-j-H-i', strtotime('+'.$dcnt.'days '.date('H:i', $tablehour), $viewtime['topw']))] as $entry)

													<div class="d-flex flex-column date-entry{{ $entry->private_entry == 1 ? ' private-entry' : '' }}{{ $entry->recurring_entry == 1 ? ' recurring-entry' : '' }}{{ !empty($entry->active_user_row) ? ' attended-entry'.(!empty($entry->onhold) ? ' onhold' : '') : '' }}" data-eid="{{ $entry->id }}" data-bs-toggle="modal" data-bs-target="#show-entry-data" tabindex="0">
@if (!empty($assets) && !empty($assets['byid']) && array_key_exists($entry->entry_type, $assets['byid']))
														<div class="w-100 d-block text-dark fw-bold">
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]))
															{{ $assets['langs'][$entry->entry_type][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
															{{ $assets['byid'][$entry->entry_type]->title }}
@endif
														</div>
@endif

														<div class="wt-event-meta d-flex flex-wrap justify-content-between">

															<span class="text-dark">
																<i class="fas fa-clock"></i>
																{{ date('H:i', strtotime($entry->entry_start)) }}
																({{ round(((strtotime($entry->entry_ending)-strtotime($entry->entry_start))/(60*60)), 1) }}@lang('racster.hour-short'))
															</span>

															<span class="text-secondary">
																<i class="fas fa-users"></i>
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
															<div class="wt-event-attend">
																@lang('racster.attend') {{ $entry_price.config('racster.transaction-token') }}
															</div>
@endif

@endif

														</div>
													</div>

@endforeach

												</div>

@endif
@php
	$tablehour+=(config('racster.timetable-range.step')*60);
@endphp

@endwhile
											</div>
										</div>
@endforeach
@endif
									</div>

								</div>

@if ($weekcnt == 7)
@php $weekcnt = 0; @endphp
@endif

@php $weekcnt++; @endphp

@endfor

							</div>
						</div>
					</div>

@php

	// Grid settings based on config
	$stepMinutes = (int) config('racster.timetable-range.step', 30);
	$startHour = (int) config('racster.timetable-range.start', 0);
	$endHour = (int) config('racster.timetable-range.end', 23);

	// Use day grid settings
	$dayStartMin = $startHour * 60;
	$dayEndMin = ($endHour+1) * 60;
	$rows = (int)(($dayEndMin - $dayStartMin) / $stepMinutes);

	// Create headers
	$days = [];
	for ($i=0; $i<7; $i++){
		$days[] = [
			'date' => date('Y-m-d', strtotime("+{$i} days", $viewtime['topw'])),
			'label' => __('racster.'.strtolower(date('l', strtotime("+{$i} days", $viewtime['topw']))).'-short')
					.' - '.date('d.m', strtotime("+{$i} days", $viewtime['topw']))
		];
	}

	// Build a date->index map for col placement (1..7)
	$dayToCol = [];
	foreach($days as $idx => $d){
		$dayToCol[$d['date']] = $idx + 1;
	}

	// Scroll-to time
	$scrollTo = config('racster.week-start-scroll', '0700');
	$scrollToHM = substr($scrollTo,0,2).':'.substr($scrollTo,2,2);

@endphp

					<div class="wt-week d-none d-lg-block" id="wt-week" data-step="{{ $stepMinutes }}" data-start-min="{{ $dayStartMin }}" data-scroll-to="{{ $scrollToHM }}" style="--rows: {{ $rows }};">

						{{-- Sticky header --}}
						<div class="wt-topbar">
							<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['prevw']) }}" class="btn btn-sm btn-link text-dark mx-1 px-2 py-0">
								<i class="fa fa-angle-left" aria-hidden="true"></i>
							</a>
							<div class="wt-title">
								{{ intval(date('W', $viewtime['topw'])) }}. @lang('racster.week')
							</div>
							<a href="{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['nextw']) }}" class="btn btn-sm btn-link text-dark mx-1 px-2 py-0">
								<i class="fa fa-angle-right" aria-hidden="true"></i>
							</a>
						</div>

						{{-- Scrollable area --}}
						<div class="wt-scroll" id="wt-scroll">

							<div class="wt-day-head">
								<div class="wt-time-head"></div>
								<div class="wt-days-head">
@foreach($days as $d)
									<div class="wt-day-label" data-day="{{ $d['date'] }}">{{ $d['label'] }}</div>
@endforeach
								</div>
							</div>

							<div class="wt-body">

								{{-- time column --}}
								<div class="wt-times">

@for($r=0; $r<=$rows; $r++)

@php

	$mins = $dayStartMin + $r * $stepMinutes;
	$h = floor($mins / 60);
	$m = $mins % 60;
	$label = sprintf('%02d:%02d', $h, $m);

@endphp

@if ($mins < $dayEndMin)
									<div class="wt-time-slot" data-min="{{ $mins }}">
										<small>{{ $m === 0 ? $label : '' }}</small>
									</div>
@endif

@endfor

								</div>

								{{-- day columns + grid lines --}}
								<div class="wt-days-grid">

@foreach($days as $d)
									<div class="wt-day-col {{ ($d['date'] === date('Y-m-d', $viewtime['nowd'])) ? 'is-today' : '' }}" data-day="{{ $d['date'] }}"></div>
@endforeach

										{{-- events layer --}}
										<div class="wt-events">

@if(!empty($events))

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

@foreach($events as $e)

@php

	$start = $e->_seg_start;
	$end = $e->_seg_end;

	$day = $start->toDateString();
	$col = $dayToCol[$day] ?? 0;

	$sMin = $start->hour * 60 + $start->minute;
	$eMin = ($end->toDateString() !== $day) ? 1440 : ($end->hour * 60 + $end->minute);

	// convert minutes to rows
	$rowStart = (int) floor(($sMin - $dayStartMin) / $stepMinutes) + 1;
	$rowEnd = (int) ceil(($eMin - $dayStartMin) / $stepMinutes) + 1;

@endphp

@if($col > 0 && $rowEnd > $rowStart)

											<div class="wt-event date-entry{{ $e->private_entry == 1 ? ' private-entry' : '' }}{{ $e->recurring_entry == 1 ? ' recurring-entry' : '' }}{{ !empty($e->active_user_row) ? ' attended-entry'.(!empty($e->onhold) ? ' onhold' : '') : '' }}"
												 style="--col: {{ $col }}; --r1: {{ $rowStart }}; --r2: {{ $rowEnd }}; --oc: {{ (int)$e->_col }}; --on: {{ (int)$e->_cols }};"
												 data-eid="{{ $e->id }}" data-bs-toggle="modal" data-bs-target="#show-entry-data" tabindex="0">

@if($e->_seg_first && !$e->_seg_last)
												<span class="wt-cont">→</span>
@elseif(!$e->_seg_first && $e->_seg_last)
												<span class="wt-cont">←</span>
@elseif(!$e->_seg_first && !$e->_seg_last)
												<span class="wt-cont">↔</span>
@endif

@if (!empty($assets) && !empty($assets['byid']) && array_key_exists($e->entry_type, $assets['byid']))

												<div class="wt-event-title fw-bold text-dark">

@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$e->entry_type][LaravelLocalization::getCurrentLocale()]))
													{{ $assets['langs'][$e->entry_type][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
													{{ $assets['byid'][$e->entry_type]->title }}
@endif

												</div>

@endif

												<div class="wt-event-meta d-flex flex-wrap justify-content-between">

													<span class="text-dark">
														<i class="d-none d-xl-inline-block fas fa-clock me-0"></i>
														{{ $start->format('H:i') }}
														<span class="d-none d-lg-inline-block ms-0">
															({{ round(($start->diffInMinutes($end) / 60), 1) }}@lang('racster.hour-short'))
														</span>
													</span>

													<span class="text-secondary">
														<i class="d-none d-xl-inline-block fas fa-users me-1"></i>
														{{ $e->client_count }}/{{ $e->client_limit }}
													</span>

@if (!in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))

@if (empty($e->active_user_row) && $start->isFuture() &&
	($start->lessThan($booking_limit) || in_array($e->entry_type, config('racster.unlimited-prebooking-limit'))) &&
	(strtotime('-'.(!empty($minperiods[$e->entry_type]) ? (int)$minperiods[$e->entry_type] : config('racster.attending-min-period')).' minutes', strtotime($e->entry_start)) >= time()) &&
	($e->client_limit - $e->client_count)
)
@php
	$entry_price = (!empty($e->entry_price) ? round($e->entry_price, 0) : config('racster.default-price'));
	if (!empty(Auth::user()->discount_amount) and Auth::user()->discount_amount > 0 and $entry_price > 0 and $e->extra_price != 1){
		$entry_price = $entry_price-round(($entry_price*Auth::user()->discount_amount/100), 0);
	}
@endphp
													<div class="wt-event-attend">
														@lang('racster.attend') {{ $entry_price.config('racster.transaction-token') }}
													</div>
@endif

@endif

												</div>

											</div>
@endif

@endforeach

@endif

									</div>

								</div>

							</div>

						</div>

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
	<script src="{{ asset('js/timetable.js') }}"></script>
	<script type="text/javascript">
		$(document).ready(function ($) {
			$('.main-alert.alert-danger').delay(5000).fadeOut('slow');
			$('.main-alert.alert-success').not('.collapse').delay(3000).fadeOut('slow');
		});
		$(".caltable, .weektable").swipe({
			preventDefaultEvents: false,
			threshold: 50,
			swipe:function(event, direction) {
				if (direction === "left"){
					location.href = '{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['nextw']) }}';
					e.stopPropagation();
				} else if(direction === "right"){
					location.href = '{{ LaravelLocalization::localizeUrl('/time/'.$viewtime['prevw']) }}';
					e.stopPropagation();
				}
			}
		});
		$(document).on('click', '.date-col-title', function(e){
			var _this = $(this);
			if ($(window).width() <= 991){
				$('.date-col-data').hide('fast');
				$('.date-col-title').removeClass('opened');
				if (_this.data('bs-target') && $(_this.data('bs-target')).is(':hidden')){
					$(_this.data('bs-target')).slideDown();
					_this.addClass('opened');
				}
			}
		});
		$(window).on("load resize", function (){
			if ($(window).width() <= 991){
				$('.date-col-data').has(".date-col-time").fadeIn('fast');
				$('.date-col-data').has(".date-col-time").parent().find('.date-col-title').addClass('opened');
			}
		});
	</script>
@endpush
