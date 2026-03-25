<section>
	<header class="mb-4">
		<h2 class="h5 fw-medium text-dark">
			@lang('racster.update-password-title')
		</h2>

		<p class="text-muted small">
			@lang('racster.update-password-description')
		</p>
	</header>

	<form method="post" action="{{ route('password.update') }}">
		@csrf
		@method('put')

		<div class="row align-items-center mb-3">
			<label for="update_password_current_password" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">@lang('racster.update-password-current-password')</label>
			<div class="col-xl-8 col-12">
				<input id="update_password_current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" />
@if ($errors->updatePassword->has('current_password'))
				<div class="text-danger small mt-1">
					{{ $errors->updatePassword->first('current_password') }}
				</div>
@endif
			</div>
		</div>

		<div class="row align-items-center mb-3">
			<label for="update_password_password" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">@lang('racster.update-password-new-password')</label>
			<div class="col-xl-8 col-12">
				<input id="update_password_password" name="password" type="password" class="form-control" autocomplete="new-password" />
@if ($errors->updatePassword->has('password'))
				<div class="text-danger small mt-1">
					{{ $errors->updatePassword->first('password') }}
				</div>
@endif
			</div>
		</div>

		<div class="row align-items-center mb-3">
			<label for="update_password_password_confirmation" class="col-xl-4 col-12 mb-0 form-label fw-semibold text-xl-end">@lang('racster.update-password-confirm-password')</label>
			<div class="col-xl-8 col-12">
				<input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" />
@if ($errors->updatePassword->has('password_confirmation'))
				<div class="text-danger small mt-1">
					{{ $errors->updatePassword->first('password_confirmation') }}
				</div>
@endif
			</div>
		</div>

		<div class="row align-items-center">
			<div class="col-xl-8 col-12 offset-xl-4 d-flex align-items-center gap-3">
				<button type="submit" class="btn btn-primary">
					@lang('racster.save')
				</button>
@if (session('status') === 'password-updated')
				<p class="small text-muted mb-0 statusMessage">@lang('racster.info-saved-successfully')</p>
@endif
			</div>
		</div>
	</form>
</section>
