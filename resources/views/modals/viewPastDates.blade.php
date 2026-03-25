<div class="modal fade" id="view-past-dates" tabindex="-1" role="dialog" aria-labelledby="@lang('racster.past-dates-modal-label')" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header py-2">
				<h4 class="modal-title">
					@lang('racster.past-dates-modal-title')
				</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
			</div>

			<div class="modal-body overflow-auto text-center" style="max-height:76vh;">

				&nbsp;

			</div>

			<div class="modal-footer py-2 justify-content-between">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
					@lang('racster.close-window')
				</button>

				<div class="ms-auto">
					&nbsp;
				</div>
			</div>

		</div>
	</div>
</div>

@push("custom_scripts")
<script type="text/javascript">

	/* Display timetable entry data */
	$(document).on('click', '#viewPastDates', function(e){
		e.stopPropagation(); e.preventDefault();
		var _this = $(this), modal = $(_this.data('bs-target'));

		// Add loading class before ajax loads
		modal.find('.modal-content').addClass("modal-loading");

		$.ajax({
			type: "POST",
			url: "{{ LaravelLocalization::localizeUrl('/acquirePastDates') }}",
			data: {
				'eid': _this.data('eid'),
				'_token': '{{ csrf_token() }}'
			},
			success: function(info){
				if (info.dates){
					var content = '';

					$.each(info.dates, function(k, u){
						content += '<li class="list-group-item list-group-item-light py-1 px-2">' +
							u.starting_date +
						'</li>';
					});

					modal.find('.modal-body').html((content != '' ? '<ul class="list-group list-group-flush">' + content + '</ul>' : ''));

				}
			},
		}).always(function(){

			// Remove loading class after ajax finishes
			modal.find('.modal-content').removeClass("modal-loading");

		});

	});

</script>
@endpush
