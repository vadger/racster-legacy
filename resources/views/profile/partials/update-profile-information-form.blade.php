<section>
	<header class="mb-4">
		<h2 class="h5 fw-medium text-dark">
			@lang('racster.profile-info-title')
		</h2>

		<p class="text-muted small">
			@lang('racster.profile-info-description')
		</p>
	</header>

	<form id="send-verification" method="post" action="{{ route('verification.send') }}">
		@csrf
	</form>

	<form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
		@csrf
		@method('patch')

		<div class="row align-items-center mb-3 d-none">
			<label for="name" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.username-field-name')*
			</label>
			<div class="col-xl-8 col-12">
				<input id="name" name="name" type="text" class="form-control" value="{{ old('name', $user->name) }}" required autocomplete="off" disabled />
				@error('name')
					<div class="text-danger small mt-1">{{ $message }}</div>
				@enderror
			</div>
		</div>

		<div class="row align-items-center mb-3">
			<label for="email" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.email-field-name')*
			</label>
			<div class="col-xl-8 col-12">

				<input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required autocomplete="off" disabled />
				@error('email')
					<div class="text-danger small mt-1">{{ $message }}</div>
				@enderror

@if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
				<div class="mt-2">
					<p class="small text-dark">
						@lang('racster.your-email-is-unverified')
						<button form="send-verification" class="btn btn-link p-0 align-baseline">
							@lang('racster.click-to-send-verification-email')
						</button>
					</p>

@if (session('status') === 'verification-link-sent')
					<p class="text-success small fw-semibold mt-1">
						@lang('racster.new-verification-email-sent')
					</p>
@endif
				</div>
@endif

			</div>
		</div>

		<div class="row align-items-center mb-3">
			<label for="first_name" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.first-name-field-name')*
			</label>
			<div class="col-xl-8 col-12">
				<input type="text" id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}" autofocus />
				@error('first_name')
					<div class="text-danger small mt-1">{{ $message }}</div>
				@enderror
			</div>
		</div>

		<div class="row align-items-center mb-3">
			<label for="last_name" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.last-name-field-name')*
			</label>
			<div class="col-xl-8 col-12">
				<input type="text" id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}" />
				@error('last_name')
					<div class="text-danger small mt-1">{{ $message }}</div>
				@enderror
			</div>
		</div>

		<div class="row align-items-center mb-3" id="profile_phone_number">
			<label for="mobile_number" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.phone-field-name')*
			</label>
			<div class="col-xl-8 col-12">
				<input type="text" id="phone_number" name="phone_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('phone_number', $user->mobile_number) }}" required />
				<input type="hidden" id="mobile_country_code" name="mobile_country_code" class="form-control @error('mobile_country_code') is-invalid @enderror" value="{{ old('mobile_country_code', $user->mobile_country_code) }}" />
				<input type="hidden" id="mobile_number" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number', $user->mobile_number) }}" />
				@error('mobile_number')
					<div class="text-danger small mt-1">{{ $message }}</div>
				@enderror
			</div>
		</div>

		<div class="row align-items-center mb-3">
			<label for="birthday" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.birthday-field-name')*
			</label>
			<div class="col-xl-8 col-12">
				<input type="text" id="birthday" name="birthday" class="form-control @error('birthday') is-invalid @enderror" value="{{ old('birthday', optional($user->birthday)->format('d.m.Y')) }}" autocomplete="off" />
				@error('birthday')
					<div class="text-danger small mt-1">{{ $message }}</div>
				@enderror
			</div>
		</div>

@if(!$user?->hasRole('manager'))
		<div class="row align-items-start mb-3">
			<label for="user_level" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.level-field-name')*
			</label>
			<div class="col-xl-8 col-12">
@if (empty($user->user_level))
				<select name="user_level" id="user_level" class="form-select" @required($user?->hasRole('client') && empty($user->user_level)) @disabled(!empty($user->user_level))>
					@foreach ($levels as $level)
						<option value="{{ $level->id }}" data-descr="{{ $level->descr }}" @if (array_key_exists($level->id, config('racster.level-videos'))) data-video="{{ config('racster.level-videos.'.$level->id) }}" @endif @selected(old('user_level', $user->user_level) == $level->id)>
							{{ $level->title }}
						</option>
					@endforeach
				</select>
				@error('level')
					<div class="text-danger small mt-1">{{ $message }}</div>
				@enderror
				<div id="level-description" class="mt-2 text-muted"></div>
				<button type="button" id="level-video-btn" class="btn btn-sm btn-outline-primary mt-2 d-none" data-bs-toggle="modal" data-bs-target="#levelVideoModal">
					@lang('racster.watch-skills-video')
				</button>
@else
@php
	$level = $levels->find($user->user_level);
@endphp
				<input id="user_level" class="form-control" value="{{ $level?->title }}" disabled />
				<div class="mt-2 text-muted">{{ $level?->descr }}</div>
@if (array_key_exists($level->id, config('racster.level-videos')))
				<button type="button" id="level-video-btn" class="btn btn-sm btn-outline-primary mt-2" data-videourl="{{ config('racster.level-videos.'.$level->id) }}" data-bs-toggle="modal" data-bs-target="#levelVideoModal">
					@lang('racster.watch-skills-video')
				</button>
@endif
@endif
				<div class="modal fade" id="levelVideoModal" tabindex="-1" aria-hidden="true">
					<div class="modal-dialog modal-lg modal-dialog-centered">
						<div class="modal-content">
							<div class="modal-body p-0">
								<iframe id="levelVideoFrame" class="rounded" width="100%" height="420" src="" frameborder="0" allow="autoplay" allowfullscreen></iframe>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
@endif

		<div class="row align-items-center mb-3">
			<label for="profile_image" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">
				@lang('racster.profile-image-field-name')
			</label>
			<div class="col-xl-8 col-12">
				<div class="input-group">
					<input type="file" id="profile_image" name="profile_image" class="form-control" />
@if ($user->profile_image)
					<a href="{{ asset('storage/' . $user->profile_image) }}" target="_blank" class="btn btn-secondary">@lang('racster.view-image')</a>
@endif
				</div>
				<div class="mt-1 small text-secondary">@lang('racster.allowed-image-types', ['maxmb' => config('racster.max_avatar_mb')])</div>
				@error('profile_image')
					<div class="invalid-feedback d-block">{{ $message }}</div>
				@enderror
			</div>
		</div>

		<div class="row align-items-center mb-1">
			<div class="col-xl-10 col-12 offset-xl-2">
				<div class="form-check">
					<input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" name="terms_accepted" id="terms_accepted" value="1" {{ old('terms_accepted', $user->terms_accepted) ? 'checked' : '' }} />
					<label class="form-check-label" for="terms_accepted">
						@lang('racster.i-accept-the-terms')
					</label>
					@error('terms_accepted')
						<div class="invalid-feedback d-block">{{ $message }}</div>
					@enderror
				</div>
			</div>
		</div>

	<div class="row align-items-center mb-3">
		<div class="col-xl-10 col-12 offset-xl-2">
			<div class="form-check">
				<input type="checkbox" id="join_newsletter" name="join_newsletter" value="1" class="form-check-input @error('join_newsletter') is-invalid @enderror" {{ old('join_newsletter', $user->join_newsletter) ? 'checked' : '' }} />
				<label for="join_newsletter" class="form-check-label">
					@lang('racster.join-newsletter-field-name')
				</label>
			</div>
			@error('join_newsletter')
				<div class="text-danger small mt-1">{{ $message }}</div>
			@enderror
		</div>
	</div>

		<div class="row align-items-center">
			<div class="col-xl-8 col-12 offset-xl-4 d-flex align-items-center gap-3">
				<button type="submit" class="btn btn-primary">@lang('racster.save')</button>
@if (session('status') === 'profile-updated')
				<p class="alert alert-primary small mb-0 p-1 statusMessage">@lang('racster.info-saved-successfully')</p>
@endif
			</div>
		</div>
	</form>
</section>

@push("custom_scripts")
	<script type="text/javascript">
		window.addEventListener('DOMContentLoaded', function () {
			const input = document.querySelector("#phone_number");
			const iti = window.intlTelInput(input, {
				initialCountry: "auto",
				geoIpLookup: callback => {
					fetch('https://ipapi.co/json')
					.then(res => res.json())
					.then(data => callback(data.country_code))
					.catch(() => callback('ee'));
				},
				separateDialCode: true,
				utilsScript: "/node_modules/intl-tel-input/build/js/utils.js"
			});
@if (!empty($user->mobile_country_code))
			iti.setNumber('{{ old("mobile_number", $user->mobile_country_code.$user->mobile_number) }}');
@endif
			// On form submit, update hidden inputs with selected code & number
			input.form.addEventListener('submit', () => {
				const countryData = iti.getSelectedCountryData();
				let countryCode = countryData.dialCode;
				let number = input.value;
				// put these into hidden fields (create them in your form)
				document.querySelector('#mobile_country_code').value = '+' + countryCode;
				document.querySelector('#mobile_number').value = number;
			});
		});
		$(document).ready(function ($) {
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
			$.datepicker.setDefaults(DATEPICKER_REGIONALS);
			var $field = $('#birthday');
			if ($field.length) {
				$field.datepicker({
					dateFormat: 'dd.mm.yy',
					changeMonth: true,
					changeYear: true,
					yearRange: '1900:+0',
					maxDate: 0,
					firstDay: 1,
					beforeShow: function (input, inst) {
						setTimeout(function () {
							inst.dpDiv.css('z-index', 7);
						}, 0);
					}
				});
			}
		});
@if(!$user?->hasRole('manager'))
		$(document).ready(function () {
			const $select = $('#user_level');
			const $descrBox = $('#level-description');
			const $btn = $('#level-video-btn');
			const $frame = $('#levelVideoFrame');

			function normalizeDriveUrl(url) {
				if (!url) return '';
				return url.replace(/\/view(\?.*)?$/, '/preview');
			}

			function updateLevelUI() {
				const $opt = $select.find('option:selected');

				const descr = $opt.data('descr') || '';
				$descrBox.text(descr);

				let videoUrl = $opt.data('video') || '';
				videoUrl = normalizeDriveUrl(($btn.data('videourl') ? $btn.data('videourl') : videoUrl));

				if (videoUrl) {
					$btn.removeClass('d-none').data('video', videoUrl);
				} else {
					$btn.addClass('d-none').removeData('video');
				}
			}
			// Update on change + on load
			$select.on('change', updateLevelUI);
			updateLevelUI();
			// When clicking the button, load the iframe
			$btn.on('click', function () {
				const videoUrl = $(this).data('video') || '';
				$frame.attr('src', videoUrl);
			});
			// Stop video when modal closes
			$('#levelVideoModal').on('hidden.bs.modal', function () {
				$frame.attr('src', '');
			});
		});
@endif
	</script>
@endpush
