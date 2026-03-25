@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-lg-12" id="manageEntryForm">

			@if (count($errors) > 999) <div class="alert alert-danger main-alert"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
			@if(Session::has('notice')) <div class="alert alert-danger main-alert text-center"> {!! Session::get('notice') !!} </div> @endif
			@if(Session::has('message')) <div class="alert alert-success main-alert text-center"> {!! Session::get('message') !!} </div> @endif

			<form method="POST" action="{{ LaravelLocalization::localizeUrl('/manage/entry'.(!empty($entry_id) ? '/'.$entry_id : '')) }}" enctype="multipart/form-data" id="entry-data">
				{{ csrf_field() }}

				<div class="card shadow rounded">
					<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
						<span>@lang('racster.timetable-manage-entry-main-header')</span>
						<div class="text-center">
							<a href="{{ LaravelLocalization::localizeUrl('/manage/entry') }}" class="btn btn-primary btn-sm mt-lg-0 mt-1">
								@lang('racster.add-new-entry')
							</a>
							<a href="{{ LaravelLocalization::localizeUrl('/timetable') }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
								@lang('racster.back-to-timetable')
							</a>
						</div>
					</div>
					<div class="card-body">

						<div class="row">
							<div class="col-lg-5">

								<div class="row mb-1">
									<label class="col-5 col-sm-3 col-lg-4 col-xl-3 col-form-label fw-bold" for="entry_title">
										@lang('racster.field-entry-title')*
									</label>
									<div class="col-7 col-sm-9 col-lg-8 col-xl-9">
										<input class="form-control{{ $errors->has('entry_title') ? ' is-invalid' : '' }}" value="{{ (old('entry_title') ? old('entry_title') : (!empty($entry_data) ? $entry_data->entry_title : '')) }}" type="text" name="entry_title" id="entry_title" autocomplete="off" />
									</div>
								</div>

								<div class="row mb-2">
									<div class="col-12">
										<textarea class="form-control{{ $errors->has('entry_description') ? ' is-invalid' : '' }}" rows="3" name="entry_description" id="entry_description" placeholder="@lang('racster.field-entry-description-placeholder')">{{ (old('entry_description') ? old('entry_description') : (!empty($entry_data) ? $entry_data->entry_description : '')) }}</textarea>
									</div>
								</div>

								<hr />

								<div class="row mb-1">
									<label class="col-5 col-sm-3 col-lg-4 col-xl-3 col-form-label fw-bold" for="entry_type">
										@lang('racster.field-entry-type')*
									</label>
									<div class="col-7 col-sm-4 col-lg-8 col-xl-5">
										<select class="form-select{{ $errors->has('entry_type') ? ' is-invalid' : '' }}" name="entry_type" id="entry_type">
											<option value="">@lang('racster.select-entry-type')</option>
@if (!empty($assets) && !empty($assets['bytype']['entry-type']))
@foreach ($assets['bytype']['entry-type'] as $type)
											<option value="{{ $type->id }}"{{ (((!empty(old('entry_type')) && old('entry_type') == $type->id) || (empty(old()) && !empty($entry_data) && $entry_data->entry_type == $type->id)) ? ' selected' : '') }}>
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$type->id][LaravelLocalization::getCurrentLocale()]))
												{{ $assets['langs'][$type->id][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
												{{ $type->title }}
@endif
											</option>
@endforeach
@endif
										</select>
									</div>
									<div class="offset-5 col-7 offset-sm-0 col-sm-5 offset-lg-4 col-lg-8 offset-xl-0 col-xl-4">
										<div class="form-check mt-1 p-0">
											<input class="form-check-input" type="checkbox" name="recurring_entry" value="Y" id="recurring_entry"{{ (((!empty(old('recurring_entry')) && old('recurring_entry') == 'Y') || (empty(old()) && !empty($entry_data) && $entry_data->recurring_entry == '1')) ? ' checked' : '') }}{{ ((!empty(old('entry_type')) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_type))) ? '' : ' disabled') }} />
											<label class="form-check-label small text-secondary" for="recurring_entry">@lang('racster.recurring-entry')</label>
										</div>
									</div>
								</div>

								<div class="row mb-1">
									<label class="col-5 col-sm-3 col-lg-4 col-xl-3 col-form-label fw-bold" for="client_level">
										@lang('racster.field-client-level')*
									</label>
									<div class="col-7 col-sm-4 col-lg-8 col-xl-5">
										<select class="form-select{{ $errors->has('client_level') ? ' is-invalid' : '' }}" name="client_level" id="client_level">
											<option value="">@lang('racster.select-client-level')</option>
@if (!empty($assets) && !empty($assets['bytype']['client-level']))
@foreach ($assets['bytype']['client-level'] as $level)
											<option value="{{ $level->id }}"{{ (((!empty(old('client_level')) && old('client_level') == $level->id) || (empty(old()) && !empty($entry_data) && $entry_data->client_level == $level->id)) ? ' selected' : '') }}>
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$level->id][LaravelLocalization::getCurrentLocale()]))
												{{ $assets['langs'][$level->id][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
												{{ $level->title }}
@endif
											</option>
@endforeach
@endif
											<option value="0"{{ ((old('client_level') == '0' || (empty(old()) && !empty($entry_data) && empty($entry_data->client_level))) ? ' selected' : '') }}>@lang('racster.for-all-client-levels')</option>
										</select>
									</div>
								</div>

								<div class="row mb-1">
									<label class="col-5 col-sm-3 col-lg-4 col-xl-3 col-form-label fw-bold" for="client_limit">
										@lang('racster.field-client-limit')*
									</label>
									<div class="col-7 col-sm-4 col-lg-8 col-xl-5">
										<input class="form-control{{ $errors->has('client_limit') ? ' is-invalid' : '' }}" value="{{ (old('client_limit') ? old('client_limit') : ((!empty($entry_data) && !empty($entry_data->client_limit)) ? $entry_data->client_limit : '1')) }}" type="number" step="1" min="1" name="client_limit" id="client_limit" autocomplete="off" />
									</div>
									<div class="offset-5 col-7 offset-sm-0 col-sm-5 offset-lg-4 col-lg-8 offset-xl-0 col-xl-4">
										<div class="form-check mt-1 p-0">
											<input class="form-check-input" type="checkbox" name="various_clients" value="Y" id="various_clients"{{ (((!empty(old('various_clients')) && old('various_clients') == 'Y') || (empty(old()) && !empty($entry_data) && $entry_data->various_clients == '1')) ? ' checked' : '') }} />
											<label class="form-check-label small text-secondary" for="various_clients">@lang('racster.various-clients')</label>
										</div>
									</div>
								</div>

								<div class="row mb-1">
									<label class="col-5 col-sm-3 col-lg-4 col-xl-3 col-form-label fw-bold" for="entry_price">
										@lang('racster.field-entry-price')* <span class="btn btn-sm btn-link p-0 tooltip-link" aria-hidden="true" data-toggle="tooltip" title="@lang('racster.price-extra-information')"><i class="fas fa-info-circle float-start"></i></span>
									</label>
									<div class="col-7 col-sm-4 col-lg-8 col-xl-5">
										<div class="input-group mb-1 mb-xl-0">
											<input class="form-control{{ $errors->has('entry_price') ? ' is-invalid' : '' }}" value="{{ (old('entry_price') ? old('entry_price') : ((!empty($entry_data) && !empty($entry_data->entry_price)) ? $entry_data->entry_price : config('racster.default-price'))) }}" type="number" step="1" min="1" name="entry_price" id="entry_price" autocomplete="off" />
											<span class="input-group-text">{{ config('racster.transaction-token') }}</span>
										</div>
									</div>
									<div class="offset-5 col-7 offset-sm-0 col-sm-5 offset-lg-4 col-lg-8 offset-xl-0 col-xl-4">
										<div class="form-check mt-1 p-0">
											<input class="form-check-input" type="checkbox" name="extra_price" value="Y" id="extra_price"{{ (((!empty(old('extra_price')) && old('extra_price') == 'Y') || (empty(old()) && !empty($entry_data) && !empty($entry_data->extra_price) && $entry_data->extra_price == '1')) ? ' checked' : '') }} />
											<label class="form-check-label small text-secondary" for="extra_price">@lang('racster.extra-price')</label>
										</div>
									</div>
								</div>

								<div class="monthly-fee-row collapse{{ (((!empty(old('recurring_entry')) && old('recurring_entry') == 'Y') || (empty(old()) && !empty($entry_data) && $entry_data->recurring_entry == '1')) ? ' show' : '') }}">
									<div class="row mb-1">
										<label class="col-5 col-sm-3 col-lg-4 col-xl-3 col-form-label fw-bold" for="entry_monthly">
											@lang('racster.field-entry-monthly-fee')*
										</label>
										<div class="col-7 col-sm-4 col-lg-8 col-xl-5">
											<div class="input-group mb-1 mb-xl-0">
												<input class="form-control{{ $errors->has('entry_monthly') ? ' is-invalid' : '' }}" value="{{ (old('entry_monthly') ? old('entry_monthly') : ((!empty($entry_data) && !empty($entry_data->entry_monthly_fee)) ? round($entry_data->entry_monthly_fee, 0) : '')) }}" type="number" step="1" min="1" name="entry_monthly" id="entry_monthly" autocomplete="off" />
												<span class="input-group-text">{{ config('racster.transaction-token') }}</span>
											</div>
										</div>
									</div>
								</div>

								<div class="row mb-1">
									<label class="col-12 col-form-label fw-bold">
										@lang('racster.field-entry-coaches')*
									</label>
									<div class="col-12">
@if (!empty($coaches))

										<ul class="list-group list-group-flush flex-row flex-wrap">
@foreach ($coaches as $ckey => $coach)
											<li class="list-group-item list-group-item-action p-1 w-auto border-0">
												<div class="form-check p-0">
													<input class="form-check-input{{ $errors->has('entry_coaches') ? ' is-invalid' : '' }}" type="checkbox" name="entry_coaches[]" value="{{ $ckey }}" id="coach_{{ $ckey }}" {{ ((in_array($ckey, old('entry_coaches', [])) || (!empty($users) && array_key_exists('coach', $users) && array_key_exists($ckey, $users['coach']))) ? 'checked' : '') }}>
													<label class="form-check-label" for="coach_{{ $ckey }}">{{ mb_ucfirst($coach) }}</label>
												</div>
											</li>
@endforeach
										</ul>

@else
										@lang('racster.no-related-users-set')
@endif
									</div>
								</div>

							</div>
							<div class="offset-lg-1 col-lg-6" id="entry-date-lines">

								<hr class="d-lg-none" />

@for($dcnt=0;$dcnt<((!empty(old('entry_start')) && old('entry_start')) ? count(old('entry_start')) : ((!empty($entry_data) && !empty($entry_data->entry_start)) ? count($entry_data->entry_start) : 1));$dcnt++)

								<div class="row entry-date-line" data-lcnt="{{ $dcnt }}"{!! ((!empty($entry_data) && !empty($entry_data->date_id) && array_key_exists($dcnt, $entry_data->date_id)) ? ' data-lid="'.$entry_data->date_id[$dcnt].'"' : '') !!}>
									<div class="col-12 col-lg-6">

										<div class="row">
											<div class="col-12">
												<div class="input-group input-group-sm">
													<span class="input-group-text">@lang('racster.field-entry-starting')</span>
													<input class="form-control startPicker{{ $errors->has('entry_start.'.$dcnt) ? ' is-invalid' : '' }}" value="{{ date('d.m.Y H:i', (old('entry_start.'.$dcnt) ? strtotime(old('entry_start.'.$dcnt)) : ((!empty($entry_data) && !empty($entry_data->entry_start)) ? strtotime($entry_data->entry_start[$dcnt]) : $basenow))) }}" type="text" name="entry_start[{{ $dcnt }}]" id="entry_start_{{ $dcnt }}" autocomplete="off"{{ ((!empty(old('entry_type')) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_type))) ? '' : ' disabled') }} />
												</div>
											</div>
											<div class="col-12 mt-1">
												<div class="collapse">
													<select class="form-select form-select-sm lengthPicker{{ $errors->has('entry_length.'.$dcnt) ? ' is-invalid' : '' }}" name="entry_length[{{ $dcnt }}]" id="entry_length_{{ $dcnt }}"{{ ((!empty(old('entry_type')) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_type))) ? '' : ' disabled') }}>
														<option value="">@lang('racster.select-entry-length')</option>
@if (!empty($assets) && !empty($assets['bytype']['entry-length']))
@foreach ($assets['bytype']['entry-length'] as $length)
														<option value="{{ $length->id }}" data-format="{{ $length->parent }}"{{ (((!empty(old('entry_start')) && old('entry_length.'.$dcnt) == $length->id) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_length) && $entry_data->entry_length[$dcnt] == $length->id)) ? ' selected' : '') }}>
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$length->id][LaravelLocalization::getCurrentLocale()]))
@if (!empty($assets['langs'][$length->id][LaravelLocalization::getCurrentLocale()]['description']))
															{{ $assets['langs'][$length->id][LaravelLocalization::getCurrentLocale()]['description'] }}
@else
															{{ $assets['langs'][$length->id][LaravelLocalization::getCurrentLocale()]['string'] }} @lang('racster.minutes-short')
@endif
@else
@if (!empty($length->descr))
															{{ $length->descr }}
@else
															{{ $length->title }} @lang('racster.minutes-short')
@endif
@endif
														</option>
@endforeach
@endif
													</select>
												</div>
												<div class="collapse">
													<div class="input-group input-group-sm">
														<span class="input-group-text">@lang('racster.field-entry-ending')</span>
														<input class="form-control endPicker{{ $errors->has('entry_ending.'.$dcnt) ? ' is-invalid' : '' }}" value="{{ date('d.m.Y H:i', (old('entry_ending.'.$dcnt) ? strtotime(old('entry_ending.'.$dcnt)) : ((!empty($entry_data) && !empty($entry_data->entry_ending) && array_key_exists($dcnt, $entry_data->entry_ending)) ? strtotime($entry_data->entry_ending[$dcnt]) : strtotime('+1 hour', $basenow)))) }}" type="text" name="entry_ending[{{ $dcnt }}]" id="entry_ending_{{ $dcnt }}" autocomplete="off"{{ ((!empty(old('entry_type')) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_type))) ? '' : ' disabled') }} />
													</div>
												</div>
											</div>
											<div class="col-12 mt-1">
												<select class="form-select form-select-sm locationPicker{{ $errors->has('entry_location.'.$dcnt) ? ' is-invalid' : '' }}" name="entry_location[{{ $dcnt }}]" id="entry_location_{{ $dcnt }}"{{ ((!empty(old('entry_type')) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_type))) ? '' : ' disabled') }}>
													<option value="">@lang('racster.select-entry-location')</option>
@if (!empty($assets) && !empty($assets['bytype']['entry-location']))
@foreach ($assets['bytype']['entry-location'] as $location)
													<option value="{{ $location->id }}"{{ (((!empty(old('entry_location')) && old('entry_location.'.$dcnt) == $location->id) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_location) && $entry_data->entry_location[$dcnt] == $location->id)) ? ' selected' : '') }}>
@if (!empty(LaravelLocalization::getSupportedLocales()) && !empty($assets['langs'][$location->id][LaravelLocalization::getCurrentLocale()]))
														{{ $assets['langs'][$location->id][LaravelLocalization::getCurrentLocale()]['string'] }}
@else
														{{ $location->title }}
@endif
													</option>
@endforeach
@endif
												</select>
											</div>
										</div>

									</div>
									<div class="col-12 col-lg-6 d-flex flex-column">

										<div class="row order-lg-3">
											<div class="col-12 mt-1 collapse{{ (((((!empty(old('various_clients')) && old('various_clients') == 'Y') || (empty(old()) && !empty($entry_data) && $entry_data->various_clients == '1')) && $dcnt > 0) || $dcnt < 1) ? ' show' : '') }}">

												<input class="form-control form-control-sm mb-1" value="" type="text" name="search_client" placeholder="@lang('racster.search-for-clients')" autocomplete="off"{{ ((!empty(old('entry_type')) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_type))) ? '' : ' disabled') }} />
												<div class="date-search"></div>
												<div class="date-clients">
													<ul class="list-group list-group-horizontal flex-wrap gap-1">
@if ((!empty($entry_data) && !empty($entry_data->clients) && !empty($entry_data->clients[$dcnt])) || (Session::has('clients-list') && !empty(Session::get('clients-list.'.$dcnt))))
@foreach (((Session::has('clients-list') && array_key_exists($dcnt, Session::get('clients-list'))) ? Session::get('clients-list.'.$dcnt) : $entry_data->clients[$dcnt]) as $ckey => $client)
														<li class="list-group-item list-group-item-action list-group-item-primary mb-0 px-1 py-0 d-flex align-items-center gap-2 w-auto border rounded">
															<div class="form-check mb-0 p-0">
																<input class="form-check-input clientPicker{{ $errors->has('entry_clients.'.$dcnt) ? ' is-invalid' : '' }}" type="checkbox" name="entry_clients[{{ $dcnt }}][]" value="{{ (Session::has('clients-list') ? $ckey : $client->id) }}" id="client_{{ $dcnt }}_{{ (Session::has('clients-list') ? $ckey : $client->id) }}" checked />
																<label class="form-check-label" for="client_{{ $dcnt }}_{{ (Session::has('clients-list') ? $ckey : $client->id) }}">
																	{{ mb_ucfirst(((Session::has('clients-list') && !is_object($client)) ? $client : $client->display_name)) }}
@if (!empty($client->cqty) && $client->cqty > 1)
																	<sup class="text-danger">*{{ $client->cqty }}</sup>
@endif
																	<input type="hidden" name="client_quantity[{{ $dcnt }}][{{ (Session::has('clients-list') ? $ckey : $client->id) }}]" value="{{ ((!empty($client->cqty) && $client->cqty > 1) ? $client->cqty : '1') }}" />
																</label>
															</div>
															<label class="date-client-pay">
																<input type="checkbox" name="entry_paying[{{ $dcnt }}][]" value="{{ (Session::has('clients-list') ? $ckey : $client->id) }}"{{ (((Session::has('clients-list') && !empty(old('entry_paying')) && !empty(old('entry_paying.'.$dcnt)) && in_array($ckey, old('entry_paying.'.$dcnt))) || (!Session::has('clients-list') && empty(old()) && $client->paying == '1')) ? ' checked' : '') }} /><i class="far fa-credit-card"></i>
															</label>
														</li>
@endforeach
@endif
													</ul>
												</div>
											</div>

										</div>
										<div class="row mt-1 mt-lg-0">
											<div class="col-6">
												<div class="form-check">
													<input class="form-check-input privacyPicker{{ $errors->has('private_entry.'.$dcnt) ? ' is-invalid' : '' }}" type="checkbox" name="private_entry[{{ $dcnt }}]" value="Y" id="private_entry_{{ $dcnt }}" {{ (((!empty(old('private_entry')) && old('private_entry.'.$dcnt) == 'Y') || (empty(old()) && !empty($entry_data) && !empty($entry_data->private_entry) && $entry_data->private_entry[$dcnt] == '1')) ? 'checked' : '') }}{{ ((!empty(old('entry_type')) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_type))) ? '' : ' disabled') }}>
													<label class="form-check-label" for="private_entry_{{ $dcnt }}">@lang('racster.private-entry')</label>
												</div>
											</div>
											<div class="col-6">
												<div class="collapse{{ (((!empty(old('entry_start')) && count(old('entry_start')) > 1) || (empty(old()) && !empty($entry_data) && !empty($entry_data->entry_start) && count($entry_data->entry_start) > 1)) ? ' show' : '') }} text-end">
													<a href="#" class="btn btn-sm btn-danger d-block d-lg-inline-block delDateLine">
														@lang('racster.delete-date-line')
													</a>
												</div>
												<input name="entry_lid[{{ $dcnt }}]" type="hidden" value="{{ (!empty(old('entry_lid')) ? old('entry_lid.'.$dcnt) : ((empty(old()) && !empty($entry_data) && !empty($entry_data->date_id) && array_key_exists($dcnt, $entry_data->date_id)) ? $entry_data->date_id[$dcnt] : '')) }}" />
											</div>
										</div>

									</div>
									<div class="col-12">
										<hr />
									</div>
								</div>

@endfor

								<div class="row mb-2">
									<div class="col-12 col-md-7 col-lg-12 col-xl-8 mb-2 mb-md-0 mb-lg-2 mb-xl-0 text-start">
										<div class="collapse{{ (((!empty(old('recurring_entry')) && old('recurring_entry') == 'Y') || (empty(old()) && !empty($entry_data) && $entry_data->recurring_entry == '1')) ? ' show' : '') }}">
											<a href="#" class="btn btn-sm btn-info d-block d-md-inline-block" id="createRecurring" data-bs-toggle="modal" data-bs-target="#add-recurring-dates">
												@lang('racster.create-recurring-dates')
											</a>
@if ($past_dates > 0 && !empty($entry_id))
											<a href="#" class="btn btn-sm btn-outline-info d-block d-md-inline-block mt-2 mt-md-0" id="viewPastDates" data-eid="{{ $entry_id }}" data-bs-toggle="modal" data-bs-target="#view-past-dates">
												@lang('racster.view-past-dates')
											</a>
@endif
										</div>
									</div>
									<div class="col-12 col-md-5 col-lg-12 col-xl-4 text-end">
										<a href="#" class="btn btn-sm btn-secondary d-block" id="newDateLine">
											@lang('racster.add-new-date-line')
										</a>
									</div>
								</div>

							</div>
						</div>

					</div>
					<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">

						<div class="text-start text-secondary">
@if ($subscriptions > 0 && !empty($entry_id))
								<a href="#" class="btn btn-sm btn-outline-primary" id="viewSubscriptions" data-eid="{{ $entry_id }}" data-bs-toggle="modal" data-bs-target="#view-entry-subs">
									@lang('racster.view-related-subscriptions')
								</a>
@else
							&nbsp;
@endif
						</div>
						<div class="text-center">
							<button name="update-entry" id="update-entry" class="btn btn-primary">@lang('racster.save-entry-data')</button>
						</div>

					</div>
				</div>

			</form>

		</div>
	</div>

@include('modals.addRecurringEntry')
@if (!empty($entry_id))
@if ($past_dates > 0)
	@include('modals.viewPastDates')
@endif
@if ($subscriptions > 0)
	@include('modals.viewActiveSubscriptions')
@endif
@endif

@endsection

@push("custom_scripts")

	<link rel="stylesheet" href="{{ asset('js/jquery-ui-timepicker-addon-1.6.3.min.css') }}">
	<script src="{{ asset('js/jquery-ui-timepicker-addon-1.6.3.min.js') }}" defer></script>

	<script type="text/javascript">

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
		// Define timepicker locals
		const TIMEPICKER_LOCALES = {
			timeText: '@lang('datepicker.time-text')',
			hourText: '@lang('datepicker.hour-text')',
			minuteText: '@lang('datepicker.minute-text')',
			currentText: '@lang('datepicker.now-text')',
			closeText: '@lang('datepicker.done-text')'
		};
		// Define datetimepickers options
		const DATETIMEPICKER_OPTIONS = {
			hour: 12, stepMinute: {{ config('racster.timetable-range.step') }}, minInterval: 0,
			hourMin: {{ config('racster.timetable-range.start') }}, hourMax: {{ config('racster.timetable-range.end') }},
			changeMonth: true, changeYear: true, showOtherMonths: false, selectOtherMonths: true,
			dateFormat: 'dd.mm.yy', timeFormat: 'HH:mm', controlType: 'select', oneLine: true
		};

		$(document).ready(function ($) {

			$('.main-alert.alert-danger').delay(5000).fadeOut('slow');
			$('.main-alert.alert-success').not('.collapse').delay(3000).fadeOut('slow');
			$('[data-toggle="tooltip"]').tooltip({ container: 'body', html: true });

			function checkClientChanges(){

				var clist = '', lim = $('#client_limit').val();

				$.each($('.entry-date-line'), function(index){

					if (!$('#various_clients').is(':checked')){

						// If any date line has different clients show all the client lists
						if (clist != '' && clist !== $(this).find('.date-clients ul').text()){
							$('.entry-date-line input[name="search_client"]').parent().addClass('show');
							$('#various_clients').prop("checked", true);
						}
						clist = $(this).find('.date-clients ul').text();

					}

					// Disable client search for datest with allowed count of clients
					if ($(this).find('.date-clients li').length >= lim){
						$(this).find('input[name="search_client"]').prop("readonly", true);
					}else{
						$(this).find('input[name="search_client"]').prop("readonly", false);
					}

				});

			}

			// Check if someone has attended the dates
			checkClientChanges();

			// Create date and clients selection by entry format
			function updateLengths(load = false){
				var _val = $('#entry_type').find('option:selected').val();
				$('.lengthPicker option:not([value=""])').hide('fast', function(){ $(this).prop("disabled", true); });
				if (_val){
					$('.startPicker, .endPicker, .lengthPicker, .locationPicker, .privacyPicker, input[name="search_client"], #recurring_entry').prop("disabled", false);
					$('.lengthPicker option[data-format="' + _val + '"]:not([value=""])').show('fast', function(){ $(this).prop("disabled", false); });
					if ($('.lengthPicker option[data-format="' + _val + '"]:not([value=""])').length > 0){
						$('.lengthPicker').closest('.collapse').slideDown('fast', function(){ $(this).addClass('show'); });
						$('.endPicker').closest('.collapse').slideUp('fast', function(){ $(this).removeClass('show'); });
						$('.endPicker').val('');
					}else{
						$('.lengthPicker').closest('.collapse').slideUp('fast', function(){ $(this).removeClass('show'); });
						$('.endPicker').closest('.collapse').slideDown('fast', function(){ $(this).addClass('show'); });
						if (load === false){
							$('.endPicker').val('{{ date('d.m.Y H:i', strtotime('+1 hour', $basenow)) }}');
						}
						$('.lengthPicker').val('');
						if ($('#recurring_entry').is(':checked')){
							$('#recurring_entry').click();
							if (load === false){
								$('.endPicker').each(function () {
									$(this).val($(this).closest('.entry-date-line').find('.startPicker').val());
								});
							}
						}
					}
				}else{
					$('.startPicker, .endPicker, .lengthPicker, .locationPicker, .privacyPicker, input[name="search_client"], #recurring_entry').prop("disabled", true);
					$('.endPicker, .lengthPicker').closest('.collapse').slideUp('fast', function(){ $(this).removeClass('show'); });
					$('.endPicker, .lengthPicker').val('');
				}
			}

			// Update date and clients selection on page load
			updateLengths(true);

			// Update date and clients selection on entry type change
			$(document).on('input', '#entry_type', function(){
				var sel = $(this);
				updateLengths();
@if (empty($entry_id))
				if (sel.val() != ''){
					$.ajax({
						type: "POST",
						url: "{{ LaravelLocalization::localizeUrl('/getPrice') }}",
						data: {
							'tid': sel.val(),
							'_token': '{{ csrf_token() }}'
						},
						success: function(info){
							if (info.success && info.price){
								$('#entry_price').val(info.price);
							}
						}
					});
				}
@endif
			});

			function initDateTimePickers() {

				// Apply datepicker and timepicker locales
				$.datepicker.setDefaults(DATEPICKER_REGIONALS);
				$.timepicker.setDefaults(TIMEPICKER_LOCALES);

				// Re-init all pickers cleanly
				$('.startPicker, .endPicker').each(function () {
					try { $(this).datetimepicker('destroy'); } catch (e) {}
					$(this).datetimepicker(DATETIMEPICKER_OPTIONS);
				});

				// Keep range consistent
				$('.startPicker').datetimepicker('option', 'onClose', function () {
					const d = $(this).datetimepicker('getDate');
					const $end = $(this).closest('.entry-date-line').find('.endPicker');
					$end.datetimepicker('option', 'minDate', d || null);
					$end.datetimepicker('option', 'minDateTime', d || null);
				});
				$('.endPicker').datetimepicker('option', 'onClose', function () {
					const d = $(this).datetimepicker('getDate');
					const $start = $(this).closest('.entry-date-line').find('.startPicker');
					$start.datetimepicker('option', 'maxDate', d || null);
					$start.datetimepicker('option', 'maxDateTime', d || null);
				});

			}

			// Init with English by default
			initDateTimePickers();

			// Reset date lines ID's
			window.resetDateLines = function resetDateLines(){
				var lcnt = 0;
				$.each($('.entry-date-line'), function(index){
					var line = $(this);

					// Reset date line count
					line.attr('data-lcnt', lcnt);
					line.find('.startPicker').attr('id', 'entry_start_' + lcnt).attr('name', 'entry_start[' + lcnt + ']');
					line.find('.endPicker').attr('id', 'entry_ending_' + lcnt).attr('name', 'entry_ending[' + lcnt + ']');
					line.find('.lengthPicker').attr('id', 'entry_length_' + lcnt).attr('name', 'entry_length[' + lcnt + ']');
					line.find('.locationPicker').attr('id', 'entry_location_' + lcnt).attr('name', 'entry_location[' + lcnt + ']');
					line.find('.privacyPicker').attr('id', 'private_entry_' + lcnt).attr('name', 'private_entry[' + lcnt + ']');
					line.find('.privacyPicker ~ label').attr('for', 'private_entry_' + lcnt);
					$.each(line.find('.clientPicker'), function(index){
						var cid = $(this).val();
						$(this).attr('id', 'client_' + lcnt + '_' + cid).attr('name', 'entry_clients[' + lcnt + '][]');
						$(this).find('~ label').attr('for', 'client_' + lcnt + '_' + cid);
						$(this).find('~ label > input').attr('name', 'client_quantity[' + lcnt + '][' + cid + ']');
					});
					$.each(line.find('.date-client-pay'), function(index){
						$(this).find('input').attr('name', 'entry_paying[' + lcnt + '][]');
					});

					// Re-init all pickers cleanly
					line.find('.startPicker, .endPicker').each(function () {
						$(this).removeClass('hasDatepicker');
						$(this).datetimepicker(DATETIMEPICKER_OPTIONS);
					});

					// Keep datepicker range consistent
					line.find('.startPicker').datetimepicker('option', 'onClose', function () {
						const d = $(this).datetimepicker('getDate');
						const $end = $(this).closest('.entry-date-line').find('.endPicker');
						$end.datetimepicker('option', 'minDate', d || null);
						$end.datetimepicker('option', 'minDateTime', d || null);
					});
					line.find('.endPicker').datetimepicker('option', 'onClose', function () {
						const d = $(this).datetimepicker('getDate');
						const $start = $(this).closest('.entry-date-line').find('.startPicker');
						$start.datetimepicker('option', 'maxDate', d || null);
						$start.datetimepicker('option', 'maxDateTime', d || null);
					});

					// Reset client search fields
					if ($('#various_clients').is(':checked') || lcnt < 1){
						line.find('input[name="search_client"]').parent().addClass('show');
					}else{
						line.find('input[name="search_client"]').parent().removeClass('show');
					}

					// Reset line IDs
					line.find('input[name^="entry_lid"]').attr('name', 'entry_lid[' + lcnt + ']');

					lcnt++;
				});
			}

			// Create new entry date line
			$(document).on('click', '#newDateLine', function(e){
				var lastline = $('.entry-date-line').last(), $clone = lastline.clone();
				e.stopPropagation(); e.preventDefault();
				$clone.find('.startPicker').val('{{ date('d.m.Y H:i', $basenow) }}');
				$clone.find('.endPicker').val('{{ date('d.m.Y H:i', strtotime('+1 hour', $basenow)) }}');
				$clone.find('.lengthPicker, .locationPicker, input[name="search_client"]').val('');
				$clone.find('.privacyPicker').prop("checked", false);
				$clone.find('.date-search').html('');
				if ($('#various_clients').is(':checked')){
					$clone.find('.date-clients ul').html('');
				}
				$clone.find('.is-invalid').removeClass('is-invalid');
				$clone.find('input[name^="entry_lid"]').val('');
				$($clone).insertAfter(lastline);
				$('.entry-date-line .delDateLine').closest('div').slideDown();
				resetDateLines();
			});

			// Delete entry date line
			$(document).on('click', '.entry-date-line .delDateLine', function(e){
				e.stopPropagation(); e.preventDefault();
				if (confirm("@lang('racster.sure-to-delete-date-line')")) {
					if ($('.entry-date-line').length >= 2){
						$(this).closest('.entry-date-line').remove();
					}
					if ($('.entry-date-line').length <= 1){
						$('.entry-date-line .delDateLine').closest('div').fadeOut();
					}
					resetDateLines();
					return true;
				} else {
					return false;
				}
			});

			// Show / hide recurring dates selection button
			$(document).on('input', '#recurring_entry', function(e){
				var _sel = $(this).is(':checked');
				e.stopPropagation(); e.preventDefault();
				if (_sel === true){
					$('#createRecurring').parent().fadeIn('fast');
					$('#entry_monthly').closest('.monthly-fee-row.collapse').slideDown('fast');
				}else{
					$('#createRecurring').parent().hide();
					$('#entry_monthly').closest('.monthly-fee-row.collapse').slideUp('fast');
					$('#entry_monthly').val('');
				}
			});

			// Show / hide recurring dates selection button
			$(document).on('input', 'input[name="client_limit"]', function(e){
				var lim = $(this).val();
				e.stopPropagation(); e.preventDefault();
				$.each($('.entry-date-line'), function(index){
					if ($(this).find('.date-clients li').length >= lim){
						$(this).find('input[name="search_client"]').val('').prop("readonly", true);
					}else{
						$(this).find('input[name="search_client"]').val('').prop("readonly", false);
					}
				});
			});

			// Show / hide clients selection with various clients
			$(document).on('input', '#various_clients', function(){
				var _sel = $(this).is(':checked'), rcnt = 0, lim = $('#client_limit').val();
				$.each($('.entry-date-line'), function(index){ console.log($(this).data('lid'));
					if (_sel === true || rcnt < 1){
						$(this).find('input[name="search_client"]').parent().addClass('show');
					}else{
						var $clone = $('.entry-date-line').first().find('.date-clients ul').clone();
						$(this).find('input[name="search_client"]').parent().removeClass('show');
						$.each($clone.find('.clientPicker'), function(index){
							var cid = $(this).val();
							$(this).attr('id', 'client_' + rcnt + '_' + cid).attr('name', 'entry_clients[' + rcnt + '][]');
							$(this).find('~ label').attr('for', 'client_' + rcnt + '_' + cid);
							$(this).find('~ label > input').attr('name', 'client_quantity[' + rcnt + '][' + cid + ']');
						});
						$.each($clone.find('.date-client-pay'), function(index){
							$(this).find('input').attr('name', 'entry_paying[' + rcnt + '][]');
						});
						$(this).find('.date-clients').html($clone);
						$(this).find('.date-search').html('');
					}
					if ($(this).find('.date-clients li').length >= lim){
						$(this).find('input[name="search_client"]').val('').prop("readonly", true);
					}else{
						$(this).find('input[name="search_client"]').val('').prop("readonly", false);
					}
					rcnt++;
				});
			});

			// Search clients by keyword and create a list
			$(document).on('focus keyup', 'input[name="search_client"]', function(){
				var sel = $(this), list = '';
				if (sel.val().length >= 3){
					$.ajax({
						type: "POST",
						url: "{{ LaravelLocalization::localizeUrl('/acquireClients') }}",
						data: {
							'kword': sel.val(),
							'_token': '{{ csrf_token() }}'
						},
						success: function(info){
							if (info.success){
								if (info.clients){
									var onlist = sel.parent().find(".clientPicker").map(function() { return $(this).val(); }).get();
									$.each(info.clients, function(k, c){
										if ($.inArray(k, onlist) === -1){
											list += '<a href="#" class="list-group-item list-group-item-action list-group-item-secondary p-1" data-uid="' + k + '" data-name="' + c + '">' + c + '</a>';
										}
									});
									sel.parent().find('.date-search').html((list !== '' ? '<div class="list-group list-group-flush mb-1">' + list + '</ul>' : ''));
								}else{
									sel.parent().find('.date-search').html('');
								}
							}
						}
					});
				} else if (sel.val().length < 1){
					sel.parent().find('.date-search').html('');
				}
			});

			
			// Create new client selection to date row
			function add_new_client(_last, _btn){
				var _lastid = _last.find('.clientPicker').attr('id'), $clone = _last.clone(), _lcnt = _lastid.match(/_(.*?)_/);
				$clone.find('.clientPicker').val(_btn.data('uid'));
				$clone.find('.clientPicker').attr('id', _lastid.replace(/_[^_]*$/, '_' + _btn.data('uid')));
				$clone.find('.form-check-label').attr('for', _lastid.replace(/_[^_]*$/, '_' + _btn.data('uid'))).html(_btn.data('name') + '<input type="hidden" name="client_quantity[' + _lcnt[1] + '][' + _btn.data('uid') + ']" value="1" />');
				$clone.find('.date-client-pay input').val(_btn.data('uid')).prop("checked", false);
				$($clone).insertAfter(_last);
			}

			// Add new clients to date and client selection
			$(document).on('click', '.date-search a', function(e){
				e.stopPropagation(); e.preventDefault();
				var _btn = $(this), lim = $('#client_limit').val();
				if (!$('#various_clients').is(':checked')){
					$.each($('.entry-date-line .date-clients'), function(index){
						if ($(this).find('li').length > 0){
							add_new_client($(this).find('li').last(), _btn);
						}else{
							var _lcnt = $(this).closest('.entry-date-line').data('lcnt'),
								_new = '<li class="list-group-item list-group-item-action list-group-item-primary mb-0 px-1 py-0 d-flex align-items-center gap-2 w-auto border rounded">' +
									'<div class="form-check mb-0 p-0">' +
										'<input class="form-check-input clientPicker" type="checkbox" name="entry_clients[' + _lcnt + '][]" value="' + _btn.data('uid') + '" id="client_' + _lcnt + '_' + _btn.data('uid') + '" checked />' +
										'<label class="form-check-label" for="client_' + _lcnt + '_' + _btn.data('uid') + '">' + _btn.data('name') + '<input type="hidden" name="client_quantity[' + _lcnt + '][' + _btn.data('uid') + ']" value="1" /></label>' +
									'</div>' +
									'<label class="date-client-pay">' +
										'<input type="checkbox" name="entry_paying[' + _lcnt + '][]" value="' + _btn.data('uid') + '" checked /><i class="far fa-credit-card"></i>' +
									'</label>' +
								'</li>';
							$(this).find('ul').html(_new);
						}
						if ($(this).find('li').length >= lim){
							$(this).closest('.collapse').find('input[name="search_client"]').val('').prop("readonly", true);
						}
					});
				}else{
					if (_btn.closest('.collapse').find('.date-clients li').length > 0){
						add_new_client(_btn.closest('.collapse').find('.date-clients li').last(), _btn);
					}else{
						var _lcnt = _btn.closest('.entry-date-line').data('lcnt'),
							_new = '<li class="list-group-item list-group-item-action list-group-item-primary mb-0 px-1 py-0 d-flex align-items-center gap-2 w-auto border rounded">' +
								'<div class="form-check mb-0 p-0">' +
									'<input class="form-check-input clientPicker" type="checkbox" name="entry_clients[' + _lcnt + '][]" value="' + _btn.data('uid') + '" id="client_' + _lcnt + '_' + _btn.data('uid') + '" checked />' +
									'<label class="form-check-label" for="client_' + _lcnt + '_' + _btn.data('uid') + '">' + _btn.data('name') + '<input type="hidden" name="client_quantity[' + _lcnt + '][' + _btn.data('uid') + ']" value="1" /></label>' +
								'</div>' +
								'<label class="date-client-pay">' +
									'<input type="checkbox" name="entry_paying[' + _lcnt + '][]" value="' + _btn.data('uid') + '" checked /><i class="far fa-credit-card"></i>' +
								'</label>' +
							'</li>';
						_btn.closest('.collapse').find('.date-clients ul').html(_new);
					}
					if (_btn.closest('.collapse').find('.date-clients li').length >= lim){
						_btn.closest('.collapse').find('input[name="search_client"]').val('').prop("readonly", true);
					}
				}
				_btn.parent().remove();
			});

			// Without various clients list make client changes for all dates
			$(document).on('click', 'input[name^="entry_clients"]', function(e){
				if (confirm("@lang('racster.sure-to-remove-the-client-from-entry-date')")) {
					var _val = $(this), lim = $('#client_limit').val();
					if (!$('#various_clients').is(':checked')){
						$.each($('.clientPicker[value="' + _val.val() + '"]'), function(index){
							$(this).prop("checked", _val.prop("checked"));
							if (_val.prop("checked") === false){
								$(this).closest('li').fadeOut('fast', function(){ $(this).remove(); });
							}
							if ($(this).closest('.date-clients').find('li').length >= lim){
								$(this).closest('.collapse').find('input[name="search_client"]').val('').prop("readonly", false);
							}
						});
					}else{
						if (_val.prop("checked") === false){
							_val.closest('li').fadeOut('fast', function(){ $(this).remove(); });
						}
						if (_val.closest('.collapse').find('.date-clients li').length >= lim){
							_val.closest('.collapse').find('input[name="search_client"]').val('').prop("readonly", false);
						}
					}
					if (_val.prop("checked") === false && !_val.closest('.date-clients').find('.alert').length){
						_val.closest('.date-clients').prepend('<div class="alert alert-warning alert-dismissible fade show py-1 small text-center" role="alert">@lang('racster.save-entry-to-confirm-clients')<button type="button" class="btn-close btn-sm p-2" data-bs-dismiss="alert" aria-label="Close"></button></div>');
						setTimeout(function() { $('.date-clients > .alert').alert('close'); }, 3000);
					}
					return true;
				} else {
					e.stopPropagation(); e.preventDefault();
					return false;
				}
			});

			// Without various clients list make paying changes to all dates
			$(document).on('input', 'input[name^="entry_paying"]', function(){
				var _val = $(this);
				if (!$('#various_clients').is(':checked')){
					$.each($('.date-client-pay input[value="' + _val.val() + '"]'), function(index){
						$(this).prop("checked", _val.prop("checked"));
					});
				}
			});

			// Without various clients list make client changes for all dates
			{{--
			$(document).on('click', '#update-entry', function(e){
				if (confirm("@lang('racster.sure-to-save-entry-data-as-entered')")) {
					return true;
				} else {
					e.stopPropagation(); e.preventDefault();
					return false;
				}
			});
			--}}

		});

	</script>
@endpush
