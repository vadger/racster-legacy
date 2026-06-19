<div class="modal fade" id="manage-date-coaches" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header py-2">
				<h4 class="modal-title">@lang('racster.field-entry-coaches')</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>

			<div class="modal-body overflow-auto pb-0 text-center" style="max-height:76vh;">
				<input type="hidden" id="date_coach_date_id" value="">

@if (!empty($coaches))
				<ul class="list-group list-group-flush text-start">
@foreach ($coaches as $ckey => $coach)
					<li class="list-group-item list-group-item-action px-0 py-1">
						<div class="form-check ps-0">
							<input class="form-check-input dateCoachPicker"
								type="checkbox"
								value="{{ $ckey }}"
								id="date_coach_{{ $ckey }}">
							<label class="form-check-label" for="date_coach_{{ $ckey }}">
								{{ mb_ucfirst($coach) }}
							</label>
						</div>
					</li>
@endforeach
				</ul>
@else
				@lang('racster.no-related-users-set')
@endif
			</div>

			<div class="modal-footer py-2 justify-content-between">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
					@lang('racster.close-window')
				</button>
				<button type="button" class="btn btn-primary" id="saveDateCoaches">
					@lang('racster.save-entry-data')
				</button>
			</div>

		</div>
	</div>
</div>

@push("custom_scripts")
<script type="text/javascript">

$(document).ready(function ($) {

	const DATE_COACHES = @json(!empty($users['date_coach']) ? $users['date_coach'] : []);

	$(document).on('click', '.manageDateCoaches', function(e){
		e.preventDefault();

		var dateId = String($(this).data('date-id'));

		$('#date_coach_date_id').val(dateId);
		$('.dateCoachPicker').prop('checked', false);

		if (DATE_COACHES[dateId] && Object.keys(DATE_COACHES[dateId]).length > 0) {

			// Date-specific coaches
			$.each(DATE_COACHES[dateId], function(coachId){
				$('#date_coach_' + coachId).prop('checked', true);
			});

		} else {

			// Fallback: currently selected primary/default coaches
			$('input[name="entry_coaches[]"]:checked').each(function(){
				$('#date_coach_' + $(this).val()).prop('checked', true);
			});

		}
	});

	function getDefaultCoachIds(){
		return $('input[name="entry_coaches[]"]:checked').map(function(){
			return String($(this).val());
		}).get().sort();
	}

	function sameCoachSelection(a, b){
		a = a.map(String).sort();
		b = b.map(String).sort();

		return JSON.stringify(a) === JSON.stringify(b);
	}

	$(document).on('click', '#saveDateCoaches', function(e){
		e.preventDefault();

		var dateId = $('#date_coach_date_id').val(),
			coaches = [];

		$('.dateCoachPicker:checked').each(function(){
			coaches.push($(this).val());
		});

		var defaultCoaches = getDefaultCoachIds(),
			saveCoaches = sameCoachSelection(coaches, defaultCoaches) ? [] : coaches;

		$.ajax({
			type: 'POST',
			url: "{{ LaravelLocalization::localizeUrl('/manage/date-coaches') }}",
			data: {
				date_id: dateId,
				coaches: saveCoaches,
				_token: '{{ csrf_token() }}'
			},
			success: function(info){
				if (info.success) {
					DATE_COACHES[dateId] = {};

					$.each(saveCoaches, function(i, coachId){
						DATE_COACHES[dateId][coachId] = true;
					});

					var btn = $('.manageDateCoaches[data-date-id="' + dateId + '"]');

					if (saveCoaches.length > 0) {
						btn.removeClass('btn-outline-secondary').addClass('btn-outline-info');
						btn.find('.date-coach-badge').removeClass('d-none');
					} else {
						btn.removeClass('btn-outline-info').addClass('btn-outline-secondary');
						btn.find('.date-coach-badge').addClass('d-none');
					}

					$('#manage-date-coaches').modal('hide');
				}
			}
		});
	});

});

</script>
@endpush
