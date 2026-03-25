<div class="modal fade" id="add-recurring-dates" tabindex="-1" role="dialog" aria-labelledby="@lang('racster.recurring-dates-modal-label')" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header py-2">
				<h4 class="modal-title">
					@lang('racster.recurring-dates-modal-title')
				</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
			</div>

			<div class="modal-body overflow-auto pb-0 text-center" style="max-height:76vh;">

				<div class="row mb-2">
					<div class="col-12 col-md-4 col-form-label text-md-end fw-bold">
						@lang('racster.field-entry-recurring-dates-days')*
					</div>
					<div class="col-12 col-md-8">
@foreach (config('racster.recurring-weekdays') as $d => $day)
						<div class="form-check d-inline-block mt-1 p-0">
							<input class="form-check-input" type="checkbox" name="recurring_day[{{ $d }}]" value="{{ ($d + 1) }}" id="recurring_day_{{ $d }}" />
							<label class="form-check-label small text-secondary" for="recurring_day_{{ $d }}">@lang('racster.'.$day.'-short')</label>
						</div>
@endforeach
					</div>
				</div>

				<div class="row mb-2">
					<label class="col-12 col-md-4 col-form-label text-md-end fw-bold" for="recurring_specs">
						@lang('racster.field-entry-recurring-dates-specification')*
					</label>
					<div class="col-12 col-md-8">
						<select class="form-select" name="recurring_specs" id="recurring_specs">
@foreach (config('racster.recurring-specs') as $specification)
							<option value="{{ $specification }}">
								@lang('racster.recurring-specification-'.$specification)
							</option>
@endforeach
						</select>
					</div>
				</div>

				<div class="row mb-2">
					<label class="col-12 col-md-4 col-form-label text-md-end fw-bold" for="recurring_period">
						@lang('racster.field-entry-recurring-dates-period')*
					</label>
					<div class="col-12 col-md-8">
						<input class="form-control" value="{{ date('d.m.Y', $basenow) }}" type="hidden" name="recurring_start" id="recurring_start" autocomplete="off" />
						<input class="form-control" value="{{ date('H:i', $basenow) }}" type="hidden" name="recurring_time" id="recurring_time" autocomplete="off" />
						<input class="form-control" value="{{ date('d.m.Y H:i', $basenow) }}" type="text" name="recurring_period" id="recurring_period" autocomplete="off" />
					</div>
				</div>

			</div>

			<div class="modal-footer py-2 justify-content-between">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
					@lang('racster.close-window')
				</button>

				<div class="ms-auto">
					<a href="#" class="btn btn-primary disabled" id="create-dates" disabled>
						@lang('racster.create-recurring-dates-modal-button')
					</a>
				</div>
			</div>

		</div>
	</div>
</div>

@push("custom_scripts")
<script type="text/javascript">

	$(document).ready(function ($) {

		/* Check if all recurring date fields are filled correctly */
		function checkRecurringTerms(){
			if ($('input[name^="recurring_day"]:checked').length > 0 && $('#recurring_specs').val().trim() !== "" && $('#recurring_period').val().trim() !== ""){
				$('#create-dates').removeClass('disabled').prop('disabled', false);
			}else{
				$('#create-dates').addClass('disabled').prop('disabled', true);
			}
		}

		/* Re-init all pickers cleanly */
		$('#recurring_period').each(function () {
			try { $(this).datepicker('destroy'); } catch (e) {}
			$(this).datepicker(DATETIMEPICKER_OPTIONS);
		});

		/* Change modal datepicker z-index */
		$(document).on('click', '#recurring_period', function(e){
			$('#ui-datepicker-div').css('z-index', $(this).closest('.modal').css('z-index'));
		});

		/* Display timetable entry data */
		$(document).on('click', '#createRecurring', function(e){
			e.stopPropagation(); e.preventDefault();
			var _this = $(this), modal = $(_this.data('bs-target'));
			modal.find('.modal-body > .alert').remove();
			$.each($('.entry-date-line').last(), function(i){
				var date = $(this).find('.startPicker').datepicker('getDate'),
					min = $(this).find('.startPicker').datepicker('getDate');
				date.setMonth(date.getMonth() + 3);
				$('#recurring_period').datepicker('setDate', date || null);
				$('#recurring_period').datepicker('option', 'minDate', min || null);
				$('#recurring_start').val($.datepicker.formatDate(DATETIMEPICKER_OPTIONS['dateFormat'], min));
				$('#recurring_time').val($.datepicker.formatTime(DATETIMEPICKER_OPTIONS['timeFormat'], { hour: min.getHours(), minute: min.getMinutes(), second: min.getSeconds() }));
				$('input[name^="recurring_day"]').prop("checked", false).filter('[name="recurring_day[' + ((min.getDay() + 6) % 7) + ']"]').prop("checked", true);
				checkRecurringTerms();
			});
		});

		/* On modal open focus recurring dates creating button */
		$('#add-recurring-dates').on('shown.bs.modal', function () {
			$('#create-dates').trigger('focus');
		});

		/* Check recurring dates fields correct filling on fields change */
		$(document).on('change', 'input[name^="recurring_day"], #recurring_specs, #recurring_period', function(e){
			checkRecurringTerms();
		});

		/* Parse date and time from predefined string date */
		function parseDateTime(dateStr) {
			let [datePart, timePart] = dateStr.split(" ");
			let [day, month, year] = datePart.split(".");
			let [hour, minute] = timePart.split(":");
			return new Date(year, month - 1, day, hour, minute);
		}

		
		/* Get event duration in minutes */
		function getEventDuration(startStr, endStr) {
			let start = parseDateTime(startStr);
			let end = parseDateTime(endStr);
			return (end - start) / 60000;
		}

		/* Parse date back to string format */
		function formatDateTime(date) {
			let d = String(date.getDate()).padStart(2, "0");
			let m = String(date.getMonth() + 1).padStart(2, "0");
			let y = date.getFullYear();
			let h = String(date.getHours()).padStart(2, "0");
			let i = String(date.getMinutes()).padStart(2, "0");
			return `${d}.${m}.${y} ${h}:${i}`;
		}

		/* Add minutes to event date */
		function addMinutesToDate(dateStr, minutesToAdd) {
			let date = parseDateTime(dateStr);
			date.setMinutes(date.getMinutes() + minutesToAdd);
			return formatDateTime(date);
		}

		/* Create recurring dates */
		$(document).on('click', '#create-dates', function(e){
			e.stopPropagation(); e.preventDefault();
			var _this = $(this), modal = $(this).closest('.modal'), _data = {};
			_this.addClass('disabled').prop('disabled', true);
			$.each($('input, select', modal),function(k, v){
				var field_name = $(this).attr('name');
				if ($(this).attr('type') == 'checkbox'){
					field_name = field_name.replace(/\[.*$/, "");
					if ($(this).is(':checked')){
						if (!_data.hasOwnProperty(field_name)){
							_data[field_name] = {};
						}
						_data[field_name][Object.keys(_data[field_name]).length] = v.value;
					}
				} else {
					_data[field_name] = v.value;
				}
			});
			if (confirm("{{ trans('racster.sure-you-want-to-create-the-recurring-dates') }}")) {
				$.ajax({
					type: "POST",
					url: "{{ LaravelLocalization::localizeUrl('/createDates') }}",
					data: {
						'data': _data,
						'_token': '{{ csrf_token() }}'
					},
					success: function(info){
						if (info.success){
							var $last = $('.entry-date-line').last();
							if ($last.find('.endPicker').closest('.collapse').is(':visible')){
								var duration = getEventDuration($last.find('.startPicker').val(), $last.find('.endPicker').val());
							}
							$.each(info.dates, function(i,v){
								var $clone = $last.clone();
								$clone.find('.startPicker').val(v);
								if ($clone.find('.endPicker').closest('.collapse').hasClass('show') && duration != ''){
									$clone.find('.endPicker').val(addMinutesToDate(v, duration));
								}else{
									$clone.find('.lengthPicker').val($last.find('.lengthPicker').val());
								}
								$clone.find('.locationPicker').val($last.find('.locationPicker').val());
								$clone.find('input[name^="entry_lid"]').val('');
								$($clone).insertBefore($last.parent().find('.row').last());
							});
							$('.entry-date-line .delDateLine').closest('div').slideDown();
							resetDateLines();
							modal.find('.modal-body').prepend('<div class="alert alert-' + (info.full === true ? 'warning' : 'success') + ' text-center">' + info.msg + '</div>');
							setTimeout(function() { modal.find('.btn-close').trigger('click'); }, 3000);
						}else{
							modal.find('.modal-body > .alert').remove();
							modal.find('.modal-body').prepend('<div class="alert alert-danger text-center">' + info.msg + '</div>');
							modal.find('.modal-body > .alert-danger').first().delay(3000).fadeOut('slow', function(){ $(this).remove(); });
						}
					},
				});
				return true;
			} else { return false; }
		});

	});

</script>
@endpush
