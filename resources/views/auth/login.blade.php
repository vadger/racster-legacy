@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
	<div class="col-md-7">
		<div class="p-4 bg-white shadow rounded">

			<h2 class="mb-4">@lang('auth.login-page-title')</h2>
			<form method="POST" action="{{ route('login') }}">
				@csrf

				<div class="mb-3">
					<label for="email" class="form-label">@lang('auth.email')</label>
					<input id="email" type="email"
						class="form-control @error('email') is-invalid @enderror"
						name="email" value="{{ old('email') }}" required autofocus>
					@error('email')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>

				<div class="mb-3">
					<label for="password" class="form-label">@lang('auth.password-field-name')</label>
					<input id="password" type="password"
						class="form-control @error('password') is-invalid @enderror"
						name="password" required>
					@error('password')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>

				<div class="mb-3 form-check ps-0">
					<input type="checkbox" class="form-check-input" name="remember" id="remember"
						{{ old('remember') ? 'checked' : '' }}>
					<label class="form-check-label" for="remember">@lang('auth.remember-me')</label>
				</div>

				<div class="row mb-3">
					<div class="col-xl-7 text-start">
						<button type="submit" class="btn btn-primary">@lang('auth.login-page-button')</button>
						@if (Route::has('password.request'))
							<a class="btn btn-link" href="{{ route('password.request') }}">
								@lang('auth.forgot-your-password')
							</a>
						@endif
					</div>
					<div class="col-xl-5 mt-4 mt-xl-0 text-xl-end text-center">
						<a href="{{ route('google.redirect') }}" class="btn btn-light text-nowrap" id="google-login">
							@lang('auth.sign-in-with-google-account-button')
						</a>
					</div>
				</div>
			</form>

		</div>
	</div>
</div>
@endsection
