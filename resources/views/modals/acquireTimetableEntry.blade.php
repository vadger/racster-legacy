<div class="modal fade" id="show-entry-data" tabindex="-1" role="dialog" aria-labelledby="@lang('racster.entry-modal-label')" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">

			<div class="modal-header py-2">
				<h4 class="modal-title">
					@lang('racster.entry-modal-title')
				</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
			</div>

			<div class="modal-body overflow-auto pb-0 text-center" style="max-height:76vh;"></div>

			<div class="modal-footer py-2 flex-column align-items-center justify-content-sm-between border-0">
@if (Auth::user()->hasRole('manager'))
				<div class="w-100 w-sm-auto">
					<a href="{{ LaravelLocalization::localizeUrl('/manage/entry') }}" class="btn btn-sm btn-outline-secondary d-block d-sm-inline-block" id="open-entry-management">
						@lang('racster.update-entry-button')
					</a>
				</div>
@endif
				<div class="order-2 d-flex align-items-center gap-1">
					<a href="#" data-attend="0" class="btn btn-primary disabled" id="attend-entry" disabled>
						@lang('racster.participate-in-the-entry')
					</a>
				</div>
			</div>

		</div>
	</div>
</div>

@push("custom_scripts")
<script type="text/javascript">

	/* Escape HTML tags in strings */
	function escapeHtml(str) {
		return $('<div/>').text(str).html().replace(/"/g, '&quot;').replace(/(?:\r\n|\r|\n)/g, '&lt;br&gt;');
	}

	/* Open Modal with hash */
	function openDateModal(){
		var hash = window.location.hash;
		if (hash.startsWith("#entry")) {
			var eid = hash.substring(6);
			var $target = $('.date-entry[data-eid="' + eid + '"]');
			if ($target.length) {
				$target.trigger('click');
			}else{
				var modal = jQuery('#show-entry-data');
				modal.find('.modal-content').addClass("modal-loading");
				modal.find('.modal-title').html('&nbsp;');
				modal.find('.modal-body').html('<div class="my-3"><span class="fw-medium">@lang('racster.no-access-due-to-participant-limit-exceeded')</span></div>');
				modal.find('.modal-content').removeClass("modal-loading");
				modal.find('.modal-footer a[data-attend]').addClass('disabled').prop('disabled', true).hide();
				modal.modal('show');
			}
			history.replaceState(null, '', location.href.split('#')[0]);
		}
	}

	$(document).ready(function ($) {
		openDateModal();
	});

	/* Open entry modal with enter */
	$(".date-entry").keypress(function(e) {
		if (e.which === 13) {
			e.stopPropagation(); e.preventDefault();
			$(this).click();
		}
	});

	/* Display timetable entry data */
	$(document).on('click', '.date-entry', function(event){
		event.stopPropagation(); event.preventDefault();
		var _this = $(this), modal = $(_this.data('bs-target'));

		// Reset coach description
		modal.find('.entry-coach-desc').remove();

		// Add loading class before ajax loads
		modal.find('.modal-content').addClass("modal-loading");

		$.ajax({
			type: "POST",
			url: "{{ LaravelLocalization::localizeUrl('/acquireEntryData') }}",
			data: {
				'eid': _this.data('eid'),
				'_token': '{{ csrf_token() }}'
			},
			success: function(info){
				if (info.entry){
					var e = info.entry, content = '', coaches = '', clients = '';
					$.each(e.users, function(k, u){
						if (u.utype == 'coach'){
							coaches += '<div class="col-4 col-lg-2 d-flex flex-column align-items-center show-coach-info"' + (u.phone ? ' data-phone="' + escapeHtml(u.phone) + '"' : '') + (u.user_desc ? ' data-desc="' + escapeHtml(u.user_desc) + '"' : '') + '>' +
								'<img class="round-user-img mb-1" src="' + (u.profile_image ? '{{ asset('storage/') }}/' + u.profile_image : '{{ asset('images/user-avatar.png') }}') +'" />' +
								'<span class="text-secondary">' + u.display_name + '</span>' +
							'</div>';
						}else{
							clients += '<div class="d-flex flex-column align-items-center mx-2" style="min-width:100px;">' +
								'<img class="round-user-img mb-1" src="' + (u.profile_image ? '{{ asset('storage/') }}/' + u.profile_image : '{{ asset('images/user-avatar.png') }}') +'" />' +
								'<span class="text-secondary">' +
@if (Auth::user()->hasRole('manager'))
									((u.payd && u.payd == 1) ? '<i class="far fa-credit-card"></i> ' : '') +
									u.display_name +
									((u.uqty && u.uqty > 1) ? '<sup class="text-danger">*' + u.uqty + '</sup>' : '') +
@else
									((u.id == '{{ Auth::user()->id }}' && u.payd && u.payd == 1) ? '<i class="far fa-credit-card"></i> ' : '') +
									u.client_name +
									((u.id == '{{ Auth::user()->id }}' && u.uqty && u.uqty > 1) ? '<sup class="text-danger">*' + u.uqty + '</sup>' : '') +
@endif
								'</span>' +
							'</div>';
						}
					});
					content +=
						'<div class="mb-2 show-coach-info"' + (e.descr ? ' data-info="level" data-desc="' + e.descr + '"' : '') + '>' +
							'<h3 class="mb-0">' + e.title + '' + (e.descr ? ' <sup><i class="fas fa-info-circle small"></i></sup>' : '') + '</h3>' + (e.typedesc ? '<small>(' + e.typedesc + ')</small>' : '') +
						'</div>' +
						'<div class="mb-2 pt-2 border-top">' +
							(e.date ? e.date + ' ' : '') + '<i class="fas fa-clock"></i> ' + e.start + '-' + e.end +
						'</div>' +
						(e.location ?
							'<div class="mb-2">' +
								(e.gmap ? '<a href="' + e.gmap + '" target="_blank" class="btn btn-sm btn-secondary">' : '') + '<i class="fas fa-map"></i> ' + e.location + (e.gmap ? '</a>' : '') +
							'</div>' : '') +
						'<div class="mb-2 show-coach-info"' + (e.leveldesc ? ' data-info="level" data-desc="' + e.leveldesc + '"' : '') + '><strong class="mb-0">@lang('racster.entry-level')</strong> ' + e.level + '</div>' +
						(coaches ? '<div class="mb-2 pt-2 border-top"><span class="fw-medium">@lang('racster.entry-coaches-title')</span><div class="text-center fst-italic small text-secondary">@lang('racster.click-on-coach-for-details')</div></div><div class="row flex-nowrap justify-content-center overflow-auto pb-2">' + coaches + '</div>' : '') +
						(clients ? '<div class="mb-2 pt-2 border-top"><span class="fw-medium">@lang('racster.entry-attendees-title')</span></div><div class="overflow-auto pb-2"><div class="d-inline-flex flex-nowrap justify-content-start">' + clients + '</div></div>' : '');
@if (!in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))
					content +=
						'<div class="pt-2 border-top" id="entryprice">' +
							(e.userprice != '' ? '<s class="text-secondary">' + e.price + '</s> ' : '') + '<h5 class="d-inline-block mb-0">' + (e.userprice != '' ? e.userprice : e.price) + '</h5>' +
							((e.recurring === true && e.recprice != '' && e.attend === true && (e.onhold != '' || e.subscribed === true)) ?
								'<div class="mb-0 small fst-italic">' + (e.reccost != '' ? '<s>' + e.recprice.split(" / ")[0] + '</s> ' + e.reccost : e.recprice) + '</div>' : '') +
						'</div>';
@endif
						
					modal.find('.modal-title').html(e.type);
					modal.find('#recurring-entry').remove();
					if (e.recurring === true){
						modal.find('.modal-header').prepend('<div id="recurring-entry"><div><span>@lang('racster.recurring-entry')</span></div></div>');
					} else if (e.privacy && e.privacy == 1) {
						modal.find('.modal-header').prepend('<div id="recurring-entry"><div class="private"><span>@lang('racster.private-entry')</span></div></div>');
					}
					modal.find('.modal-body').html(content);
@if (Auth::user()->hasRole('manager'))
					modal.find('.modal-footer #open-entry-management').attr('href', '{{ LaravelLocalization::localizeUrl('/manage/entry') }}/' + e.eid);
@endif
					modal.find('.modal-footer a[data-attend]').parent().find('small').remove();
					modal.find('.modal-footer #clientCount, .modal-footer #annulment').remove();
@if (in_array(config('racster.coaches_role'), explode('|', Auth::user()->user_roles)))
					modal.find('.modal-footer a[data-attend]').attr('href', '#').hide();
@else
					modal.find('.modal-footer a[data-attend]').data('attend', e.id).text('@lang('racster.participate-in-the-entry')');
					modal.find('.modal-footer a[data-attend]').attr('href', '#').removeClass('btn-info').removeClass('disabled').show();
					if (e.attend === true){
						modal.find('.modal-footer a[data-attend]').addClass('btn-info').text((e.onhold != '' ? (e.recurring === true ? '@lang('racster.pay-for-subscription')' : '@lang('racster.pay-for-onetime')') : '@lang('racster.cancel')'));
						if (e.subscribed === true){
							modal.find('.modal-footer a[data-attend]').parent().prepend('<small class="text-secondary fst-italic">@lang('racster.subscribed')</small>');
						}
						if (e.onhold != ''){
							if (e.onhold == 'newone'){
								modal.find('.modal-footer a[data-attend]').attr('href', '{{ LaravelLocalization::localizeUrl('/checkout/attend') }}/' + e.id);
							} else if (e.onhold == 'newsub'){
								modal.find('.modal-footer a[data-attend]').attr('href', '{{ LaravelLocalization::localizeUrl('/checkout/subscribe') }}/' + e.eid);
							} else {
								modal.find('.modal-footer a[data-attend]').attr('href', '{{ LaravelLocalization::localizeUrl('/checkout/continue') }}/' + e.onhold);
							}
						}
						if (e.pastentry === true){
							modal.find('.modal-footer a[data-attend]').parent().prepend('<small class="text-secondary">@lang('racster.min-cancellation-time-has-passed')' + (e.subscribed === true ? ' | ' : '') + '</small>');
							if (e.onhold == '' || e.started === true){
								modal.find('.modal-footer a[data-attend]').addClass('disabled').prop('disabled', true);
							}
						} else {
							modal.find('.modal-footer a[data-attend]').removeClass('disabled').prop('disabled', false);
						}
					} else if (e.limfuture === true){
						modal.find('.modal-footer a[data-attend]').parent().prepend('<small class="text-secondary">@lang('racster.prebooking-is-limited-by')</small>');
						modal.find('.modal-footer a[data-attend]').addClass('disabled').prop('disabled', true).hide();
					} else if ((e.limit-e.used) < 1 || e.pastentry === true || e.available === false){
						if ((e.pastentry === true && e.available === false) || (e.pastentry === false && e.available === false)){
							modal.find('.modal-footer a[data-attend]').parent().prepend('<small class="text-secondary">@lang('racster.min-participation-time-has-passed')</small>');
						}
						if (e.available === true && e.started === false){
							modal.find('.modal-footer a[data-attend]').addClass('btn-info').text('@lang('racster.pay-for-onetime')');
						}else{
							modal.find('.modal-footer a[data-attend]').addClass('disabled').prop('disabled', true).hide();
						}
					} else {
						if (e.okcredit === false){
							modal.find('.modal-footer a[data-attend]').addClass('btn-info').text('@lang('racster.pay-for-onetime')');
							if (e.balance > 0){
								modal.find('.modal-footer a[data-attend]').parent().prepend('<small class="form-check d-inline-block me-1"><input class="form-check-input" type="checkbox" name="usecredit" id="usecredit" /><label class="form-check-label" for="usecredit">@lang('racster.use-credit')</label></small>');
							}
						}
						if (e.recurring === false && e.noprivate === false && (e.limit-e.used) > 1 && e.limit <= {{ config('racster.max-private-clients') }}){
							var options = [];
							for (var i=1;i<=(e.limit-e.used);i++) {
								options.push('<option value="' + i + '">' + i + '</option>');
							}
							modal.find('.modal-footer a[data-attend]').parent().prepend('<select class="form-select d-inline-block w-auto" id="clientCount">' + options.join("") + '</select>');
							if (e.limit == (e.limit-e.used) && (!e.privacy || e.privacy != 1)){
								modal.find('.modal-footer a[data-attend]').parent().prepend('<small class="form-check d-inline-block me-1"><input class="form-check-input" type="checkbox" name="privately" id="privately" data-did="' + e.id + '" /><label class="form-check-label" for="privately">@lang('racster.i-want-privately')</label></small>');
							}
						}

						modal.find('.modal-footer a[data-attend]').removeClass('disabled').prop('disabled', false);
					}
@if (Auth::user()->hasRole('manager'))
					modal.find('.modal-footer a[data-attend]').addClass('disabled').prop('disabled', true).hide();
@endif
@endif
					if (e.pastentry === false && e.annulment != ''){
						$('<div class="order-last text-center text-secondary fst-italic lh-sm" id="annulment"><small>' + e.annulment + '</small></div>').insertAfter(modal.find('.modal-footer a[data-attend]').parent());
					}
				}
			},
		}).always(function(){
			// Remove loading class after ajax finishes
			modal.find('.modal-content').removeClass("modal-loading");
		});

	});

	/* Display coach description */
	$(document).on('click', '.show-coach-info', function(e){
		e.stopPropagation(); e.preventDefault();
		$(this).closest('.modal-body').find('.entry-coach-desc').remove();
		if ($(this).data('desc')){
			var desc = '<div class="d-flex entry-coach-desc">' +
				'<div class="p-3 overflow-auto">' +
					(!$(this).data('info') ?
					'<div class="row align-items-center">' +
						'<div class="col-12 offset-lg-1 col-lg-4 text-end">' +
							'<img class="coach-image mb-1" src="' + $(this).find('img').attr('src') +'" />' +
						'</div>' +
						'<div class="col-12 col-lg-6 text-center text-lg-start">' +
							'<h4 class="mb-3">' + $(this).find('span').text() + '</h4>' +
							($(this).data('phone') ? '<div class="my-2"><a href="tel:' + $(this).data('phone') + '" class="btn btn-sm btn-light border-dark">' + $(this).data('phone') + '</a></div>' : '') +
						'</div>' +
					'</div>' : '') +
					'<div class="row mt-2">' +
						'<div class="col-12 offset-lg-1 col-lg-10">' +
							$(this).data('desc') +
							'<a href="#" class="d-block mt-3 btn btn-secondary">@lang('racster.back-to-entry-info')</a>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>';
			$(this).closest('.modal-body').append(desc);
			$(this).closest('.modal-body').find('.entry-coach-desc a').focus();
		}
	});

	/* Hide coach description */
	$(document).on('click', '.entry-coach-desc a[href="#"]', function(e){
		var modal = $(this).closest('.modal');
		e.stopPropagation(); e.preventDefault();
		$(this).closest('.entry-coach-desc').remove();
		modal.focus();
	});


	/* Get updated price with private entry */
	$(document).on('input', '#privately', function(e){
		var _this = $(this);
		$.ajax({
			type: "POST",
			url: "{{ LaravelLocalization::localizeUrl('/acquirePrice') }}",
			data: {
				'did': _this.data('did'),
				'private': _this.is(':checked'),
				'_token': '{{ csrf_token() }}'
			},
			success: function(info){
				if (info.success){
					$('#entryprice').html(
						(info.userprice != '' ? '<s class="text-secondary">' + info.price + '</s> ' : '') +
						'<h5 class="d-inline-block mb-0">' + (info.userprice != '' ? info.userprice : info.price) + '</h5>'
					);
				}
			},
		});
	});

	/* Attend to entry */
	$(document).on('click', '#attend-entry[href="#"]', function(e){
		e.stopPropagation(); e.preventDefault();
		var _this = $(this), modal = $(this).closest('.modal');
		if (confirm((_this.text() == '@lang('racster.cancel')' ? "{{ trans('racster.sure-you-want-to-cancel-the-attendance') }}" : "{{ trans('racster.sure-you-want-to-attend-the-entry') }}"))) {
			$.ajax({
				type: "POST",
				url: "{{ LaravelLocalization::localizeUrl('/attendPeriod') }}",
				data: {
					'eid': _this.data('attend'),
					'cqty': ($("#clientCount").length ? $('#clientCount').val() : 1),
					'credit': _this.parent().find("#usecredit").is(':checked'),
					'private': _this.parent().find("#privately").is(':checked'),
					'_token': '{{ csrf_token() }}'
				},
				success: function(info){
					if (info.success){
						if (info.pay_url){
							window.location.href = info.pay_url;
						}else{
							modal.find('.modal-body').prepend('<div class="alert alert-' + (info.full === true ? 'warning' : 'success') + ' text-center">' + info.msg + '</div>');
							setTimeout(function() { window.location.reload(); }, 3000);
						}
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

</script>
@endpush
