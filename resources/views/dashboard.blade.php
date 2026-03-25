@extends('layouts.app')

@section('content')

<div class="row gy-4">
	<div class="col-12 col-md-10 col-lg-6 mx-auto">
		<div class="p-4 bg-white shadow rounded">

			<div class="row">
				<div class="col-12">
					<h3>@lang('racster.you-are-logged-in', ['name' => (Auth::user()->first_name ?? Auth::user()->name)])</h3>
				</div>
			</div>

			<div class="row">
				<div class="col-12">

					<div class="small text-secondary">
						@lang('racster.your-next-entry')
					</div>

					<div class="alert alert-primary mb-3 py-3">

@if (!empty($next_entry))

						<div class="row align-items-center">
							<div class="col-12 col-md-6 mb-2 text-center">
								<a href="{{ LaravelLocalization::localizeUrl('/time/'.strtotime($next_entry->entry_start)) }}" class="h4 mb-0 text-decoration-none">
									{{ date('d.m.Y H:i', strtotime($next_entry->entry_start)) }}
								</a>
							</div>
							<div class="col-12 col-md-6 mb-2 text-center">
@if (!empty($assets) && !empty($assets['byid']) && array_key_exists($next_entry->entry_location, $assets['byid']))

@php
	if (!empty(LaravelLocalization::getSupportedLocales()) &&
		!empty($assets['langs'][$next_entry->entry_location][LaravelLocalization::getCurrentLocale()]) &&
		!empty($assets['langs'][$next_entry->entry_location][LaravelLocalization::getCurrentLocale()]['description'])
	){
		$gmap = $assets['langs'][$next_entry->entry_location][LaravelLocalization::getCurrentLocale()]['description'];
	}else{
		$gmap = $assets['byid'][$next_entry->entry_location]->descr;
	}
@endphp

@if (!empty($gmap) && !empty($assets) && !empty($assets['byid']) && array_key_exists($next_entry->entry_type, $assets['byid']))
								<a href="{{ $gmap }}" target="_blank" class="btn btn-sm btn-secondary">
									<i class="fas fa-map"></i>
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$next_entry->entry_location][LaravelLocalization::getCurrentLocale()]))
									{{ $assets['langs'][$next_entry->entry_location][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
									{{ $assets['byid'][$next_entry->entry_location]->title }}
@endif
								</a>
@endif

@endif
							</div>

							<div class="col-12">
								<hr class="my-2 text-primary" />
							</div>

							<div class="col-12 col-md-6 text-center">

@if (!empty($assets) && !empty($assets['byid']) && array_key_exists($next_entry->entry_type, $assets['byid']))
								<span class="d-block fw-medium">
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$next_entry->entry_type][LaravelLocalization::getCurrentLocale()]))
									{{ $assets['langs'][$next_entry->entry_type][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
									{{ $assets['byid'][$next_entry->entry_type]->title }}
@endif
								</span>
@endif
							</div>
							<div class="col-12 col-md-6 text-center">
								<small class="d-block mb-1">{{ $next_entry->entry_title }}</small>
							</div>

						</div>
@else
						<div class="row align-items-center">
							<div class="col-12 text-center">
								@lang('racster.no-next-entry')
							</div>
						</div>
@endif

					</div>

				</div>
			</div>

			<div class="row">
				<div class="offset-0 col-12 offset-md-6 col-md-6">
					<a href="{{ LaravelLocalization::localizeUrl('/timetable') }}" class="btn btn-primary d-block">
						@lang('racster.go-to-timetable')
					</a>
				</div>
			</div>

		</div>
	</div>
	<div class="col-12 col-md-10 col-lg-3 mx-auto">

		<div class="card shadow rounded h-100">
			<div class="card-header text-center">@lang('racster.dashboard-balance-card-header')</div>
			<div class="card-body">

				<div class="row h-100 flex-column justify-content-center">
					<div class="col-12 mb-4 text-center">
						@lang('racster.your-transactions-balance', ['balance' => $balance.config('racster.transaction-token')])
					</div>
					<div class="col-12 mb-2">
						<a href="{{ LaravelLocalization::localizeUrl('/users/view-credit') }}" class="btn btn-light border-secondary d-block">
							@lang('racster.view-transactions')
						</a>
					</div>
					<div class="col-12 mb-2">
						<a href="{{ LaravelLocalization::localizeUrl('/subscriptions') }}" class="btn btn-light border-secondary d-block">
							@lang('racster.view-subscriptions')
						</a>
					</div>
				</div>

			</div>
		</div>

	</div>
	<div class="col-12 col-md-10 col-lg-3 mx-auto">

		<div class="card shadow rounded h-100">
			<div class="card-header bg-light text-secondary text-center">@lang('racster.dashboard-settings-card-header')</div>
			<div class="card-body">

				<div class="row h-100 flex-column justify-content-center">
					<div class="col-12 mb-2">
						<a href="{{ LaravelLocalization::localizeUrl('/notifications') }}" class="btn btn-light border-secondary d-block">
							@lang('racster.view-notifications')
						</a>
					</div>
					<div class="col-12 mb-2">
						<a href="{{ LaravelLocalization::localizeUrl('/profile') }}" class="btn btn-light border-secondary d-block">
							@lang('racster.manage-profile')
						</a>
					</div>
				</div>

			</div>
		</div>

	</div>
</div>

@endsection
