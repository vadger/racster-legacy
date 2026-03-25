<div class="modal fade" id="view-entry-subs" tabindex="-1" role="dialog" aria-labelledby="@lang('racster.subscriptions-modal-label')" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header py-2">
				<h4 class="modal-title">
					@lang('racster.subscriptions-modal-title')
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
	$(document).on('click', '#viewSubscriptions', function(e){
		e.stopPropagation(); e.preventDefault();
		var _this = $(this), modal = $(_this.data('bs-target'));

		// Add loading class before ajax loads
		modal.find('.modal-content').addClass("modal-loading");

		$.ajax({
			type: "POST",
			url: "{{ LaravelLocalization::localizeUrl('/acquireEntrySubscriptions') }}",
			data: {
				'eid': _this.data('eid'),
				'_token': '{{ csrf_token() }}'
			},
			success: function(info){
				if (info.subs){
					var content = '';

					$.each(info.subs, function(k, u){
						content += '<li class="list-group-item list-group-item-action list-group-item-light py-1 px-2">' +
							'<a href="{{ LaravelLocalization::localizeUrl('/users/subscription') }}/' + u.subid + '" target="_blank" class="d-flex align-items-center justify-content-between text-decoration-none">' +
								'<span>' +
									'<span class="fw-medium">' + u.display_name + '</span>' +
									' <small>(' + (parseFloat(u.amount)/100) + ' ' + u.currency.toUpperCase() + ')</small>' +
								'</span>' +
								(u.ending_date ? '<small>' + u.ending_date + '</small>' : '') +
							'</a>' +
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
