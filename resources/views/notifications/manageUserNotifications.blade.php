@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-xl-10" id="user-notifications">

			@if (Auth::user()->hasRole('client'))

				@if (count($errors) > 999) <div class="alert alert-danger"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
				@if(Session::has('notice')) <div class="alert alert-danger text-center"> {!! Session::get('notice') !!} </div> @endif
				@if(Session::has('message')) <div class="alert alert-success text-center"> {!! Session::get('message') !!} </div> @endif

				<div class="card shadow rounded">
					<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
						<span class="fw-bold">
							@lang('racster.notifications-main-card-header')
						</span>
					</div>
					<div class="card-body">

						<div class="row">
							<div class="col-12 mb-2">
								<div class="alert alert-primary mb-0 small">
									@lang('racster.notifications-main-card-description')
								</div>
							</div>
						</div>

@if (!empty($coaches))
						<div class="row my-1">
							<div class="col-12 col-sm-6 fw-bold">
								@lang('racster.notifications-select-coaches-title')
							</div>
							<div class="col-12 col-sm-6 mt-1 mt-sm-0 text-end">
								<button type="button" class="btn btn-sm btn-outline-info btn-check-all" data-target="coaches[]">@lang('racster.notifications-select-all')</button>
								<button type="button" class="btn btn-sm btn-outline-info btn-uncheck-all" data-target="coaches[]">@lang('racster.notifications-clear-all')</button>
							</div>
							<div class="col-12 d-flex flex-wrap gap-2 row-gap-1 ps-3 mt-1 mb-2">
@foreach ($coaches as $ckey => $coach)
								<div class="form-check p-0">
									<input class="form-check-input{{ $errors->has('coaches') ? ' is-invalid' : '' }}" type="checkbox" name="coaches[]" value="{{ $ckey }}" id="coach_{{ $ckey }}" @checked($filters->coaches->contains('id', $ckey))>
									<label class="form-check-label" for="coach_{{ $ckey }}">{{ mb_ucfirst($coach) }}</label>
								</div>
@endforeach
							</div>
						</div>
@endif

@if (!empty($assets) && !empty($assets['bytype']['entry-type']))
						<div class="row my-1">
							<div class="col-12 col-sm-6 fw-bold">
								@lang('racster.notifications-select-types-title')
							</div>
							<div class="col-12 col-sm-6 mt-1 mt-sm-0 text-end">
								<button type="button" class="btn btn-sm btn-outline-info btn-check-all" data-target="types[]">@lang('racster.notifications-select-all')</button>
								<button type="button" class="btn btn-sm btn-outline-info btn-uncheck-all" data-target="types[]">@lang('racster.notifications-clear-all')</button>
							</div>
							<div class="col-12 d-flex flex-wrap gap-2 row-gap-1 ps-3 mt-1 mb-2">
@foreach ($assets['bytype']['entry-type'] as $type)
								<div class="form-check p-0">
									<input class="form-check-input{{ $errors->has('types') ? ' is-invalid' : '' }}" type="checkbox" name="types[]" value="{{ $type->id }}" id="type_{{ $type->id }}" @checked($filters->trainingTypes->contains('id', $type->id))>
									<label class="form-check-label" for="type_{{ $type->id }}">{{ mb_ucfirst($type->title) }}</label>
								</div>
@endforeach
							</div>
						</div>
@endif
@if (!empty($assets) && !empty($assets['bytype']['entry-location']))
						<div class="row my-1">
							<div class="col-12 col-sm-6 fw-bold">
								@lang('racster.notifications-select-locations-title')
							</div>
							<div class="col-12 col-sm-6 mt-1 mt-sm-0 text-end">
								<button type="button" class="btn btn-sm btn-outline-info btn-check-all" data-target="locations[]">@lang('racster.notifications-select-all')</button>
								<button type="button" class="btn btn-sm btn-outline-info btn-uncheck-all" data-target="locations[]">@lang('racster.notifications-clear-all')</button>
							</div>
							<div class="col-12 d-flex flex-wrap gap-2 row-gap-1 ps-3 mt-1 mb-2">
@foreach ($assets['bytype']['entry-location'] as $location)
								<div class="form-check p-0">
									<input class="form-check-input{{ $errors->has('locations') ? ' is-invalid' : '' }}" type="checkbox" name="locations[]" value="{{ $location->id }}" id="location_{{ $location->id }}" @checked($filters->locations->contains('id', $location->id))>
									<label class="form-check-label" for="location_{{ $location->id }}">{{ mb_ucfirst($location->title) }}</label>
								</div>
@endforeach
							</div>
						</div>
@endif

					</div>
					<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">
						<div class="ms-lg-auto text-center">
							<button id="toggle-active-btn" class="btn btn-{{ $filters->is_active ? 'outline-secondary' : 'primary' }}" data-active="{{ $filters->is_active ? '1' : '0' }}">
								{{ trans('racster.'.($filters->is_active ? 'disable' : 'enable').'-notifications') }}
							</button>
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
			function getValues(name) {
				return $('input[name="' + name + '"]:checked').map(function () { return $(this).val(); }).get();
			}
			function saveFilters() {
				const coaches = getValues('coaches[]');
				const locations = getValues('locations[]');
				const trainingTypes = getValues('types[]');
				$.ajax({
					url: "{{ LaravelLocalization::localizeUrl('/notifications/update-filters') }}",
					type: 'POST',
					data: {
						'_token': '{{ csrf_token() }}',
						coach_ids: coaches,
						location_ids: locations,
						training_type_ids: trainingTypes
					},
					success: function (response) {
						$('#user-notifications .card-body > .alert').remove();
						$('#user-notifications .card-body').prepend('<div class="alert alert-success text-center">' + response.msg + '</div>');
						$('#user-notifications .card-body > .alert').first().delay(5000).fadeOut('slow', function(){ $(this).remove(); });
					}
				});
			}
			$('.form-check-input').on('change', saveFilters);
			$('#toggle-active-btn').on('click', function () {
				const $btn = $(this);
				$('#user-notifications .card-body > .alert').remove();
				$.ajax({
					url: "{{ LaravelLocalization::localizeUrl('/notifications/toggle-active') }}",
					type: "POST",
					data: {
						'_token': '{{ csrf_token() }}',
					},
					success: function (response) {
						if (response.is_active) {
							$btn.text('@lang('racster.disable-notifications')');
							$btn.data('active', 1);
							$btn.removeClass('btn-primary').addClass('btn-outline-secondary');
							$('.form-check-input, .btn-check-all, .btn-uncheck-all').prop('disabled', false);
							$('#user-notifications .card-body').prepend('<div class="alert alert-success text-center">@lang('racster.notifications-successfully-activated')</div>');
						} else {
							$btn.text('@lang('racster.enable-notifications')');
							$btn.data('active', 0);
							$btn.removeClass('btn-outline-secondary').addClass('btn-primary');
							$('.form-check-input, .btn-check-all, .btn-uncheck-all').prop('disabled', true);
							$('#user-notifications .card-body').prepend('<div class="alert alert-success text-center">@lang('racster.notifications-successfully-disabled')</div>');
						}
						$('#user-notifications .card-body > .alert').first().delay(5000).fadeOut('slow', function(){ $(this).remove(); });
					}
				});
			});
			const isActive = Number($('#toggle-active-btn').data('active'));
			if (isActive === 0) {
				$('.form-check-input, .btn-check-all, .btn-uncheck-all').prop('disabled', true);
			} else {
				$('.form-check-input, .btn-check-all, .btn-uncheck-all').prop('disabled', false);
			}
			// Check all checkboxes
			$('.btn-check-all').on('click', function () {
				const target = $(this).data('target');
				$('input[name="' + target + '"]').prop('checked', true).trigger('change');
			});
			// Uncheck all checkboxes
			$('.btn-uncheck-all').on('click', function () {
				const target = $(this).data('target');
				$('input[name="' + target + '"]').prop('checked', false).trigger('change');
			});
		});
	</script>
@endpush
