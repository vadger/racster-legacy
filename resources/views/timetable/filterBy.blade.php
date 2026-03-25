<div class="modal fade" id="manage-settings" tabindex="-1" role="dialog" aria-labelledby="@lang('racster.timetable-settings-modal-label')" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-header py-2">
				<h4 class="modal-title">
					@lang('racster.timetable-settings-modal-title')
				</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
			</div>

			<div class="modal-body overflow-auto pb-0 text-center" style="max-height:76vh;">

				<ul class="list-group list-group-flush text-start">
					<li class="list-group-item list-group-item-action p-0">
						<div class="form-radio">
							<input type="radio" name="active_entries" id="active_entries_mine" value="mine"{{ ((!empty(Auth::user()->active_entries) && Auth::user()->active_entries == 'mine') ? ' checked' : '') }} />
							<label for="active_entries_mine" class="mb-0 px-0 py-2">@lang('racster.hide-my-entries')</label>
						</div>
					</li>
					<li class="list-group-item list-group-item-action p-0">
						<div class="form-radio">
							<input type="radio" name="active_entries" id="active_entries_free" value="free"{{ ((!empty(Auth::user()->active_entries) && Auth::user()->active_entries == 'free') ? ' checked' : '') }} />
							<label for="active_entries_free" class="mb-0 px-0 py-2">@lang('racster.hide-free-entries')</label>
						</div>
					</li>
				</ul>

			</div>

			<div class="modal-footer py-2 justify-content-between">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
					@lang('racster.close-window')
				</button>

				<div class="ms-auto">
					<a href="#" class="btn btn-primary" id="active-all" disabled>
						@lang('racster.show-all-entries-modal-button')
					</a>
				</div>
			</div>

		</div>
	</div>
</div>

@push("custom_scripts")
	<script type="text/javascript">
		$(document).on('input', 'select[name^="filterby_"]', function(e){
			var select = $(this), filter = select.attr('name').replace('filterby_', ''), filterby = select.val();
			e.stopPropagation(); e.preventDefault();
			$.ajax({
				type: "POST",
				url: "{{ LaravelLocalization::localizeUrl('/addFilter') }}",
				data: {
					'filter': filter,
					'filterby': filterby,
					'view': 'timetable',
					'_token': '{{ csrf_token() }}'
				},
				success: function(info){
					if (info.success){
						window.location.replace("{{ LaravelLocalization::localizeUrl('/timetable') }}");
					}else{
						select.closest('.card-body > .alert').remove();
						select.closest('.card-body').prepend('<div class="alert alert-danger text-center">' + info.msg + '</div>');
						select.closest('.card-body > .alert-danger').first().delay(5000).fadeOut('slow', function(){ $(this).remove(); });
					}
				}
			});
		});
		$(document).on('change', '[name="active_entries"]', function(){
			$.ajax({
				type: "POST",
				url: "{{ url('/changeSetting') }}",
				data: {
					'setting' : $(this).val(),
					'_token': '{{ csrf_token() }}'
				},
				success: function(info){
					window.location.replace("{{ url('/'.Request::path()) }}");
				}
			});
		});
		$(document).on('click', '#active-all', function(){
			$.ajax({
				type: "POST",
				url: "{{ url('/changeSetting') }}",
				data: {
					'setting' : $(this).attr('id').replace('active-', ''),
					'_token': '{{ csrf_token() }}'
				},
				success: function(info){
					window.location.replace("{{ url('/'.Request::path()) }}");
				}
			});
		});
	</script>
@endpush
