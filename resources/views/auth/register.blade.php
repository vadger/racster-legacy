@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
	<div class="col-md-7">
		<div class="p-4 bg-white shadow rounded">

			<h2 class="mb-4">@lang('auth.register-page-title')</h2>

			<div class="row mt-4 mb-0">
				<div class="col-12 text-center">
					<a href="{{ route('google.redirect') }}" class="btn btn-light text-nowrap" id="google-login">
						@lang('auth.register-with-google-account-button')
					</a>
				</div>
				<div class="col-12 text-center">
					<div class="d-flex align-items-center my-4">
						<hr class="flex-grow-1">
							<span class="mx-3">@lang('auth.or')</span>
						<hr class="flex-grow-1">
					</div>
				</div>
			</div>

			<h4 class="mb-4">@lang('auth.register-page-create-account-title')</h4>

			@error('email')
				<div class="alert alert-danger mb-2 p-2 small text-center">
					{{ $message }}
				</div>
			@enderror

			<form method="POST" action="{{ route('register') }}">
				@csrf
				<input id="name" type="hidden" class="form-control d-none" name="name" value="{{ old('name') }}" required readonly>

				<div class="mb-3">
					<label for="email" class="form-label">@lang('auth.email')</label>
					<input id="email" type="email"
						class="form-control"
						name="email" value="{{ old('email') }}" required autofocus>
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

				<div class="mb-3">
					<label for="password_confirmation" class="form-label">@lang('auth.confirm-password')</label>
					<input id="password_confirmation" type="password"
						class="form-control"
						name="password_confirmation" required>
				</div>

				<button type="submit" class="btn btn-primary">@lang('auth.register-page-button')</button>
			</form>

		</div>
	</div>
</div>
@endsection

@push("custom_scripts")

	<script type="text/javascript">
		$(document).ready(function ($) {
			$('#email').on('input', function () {
				let email = $(this).val();
				if (email.includes('@')) {
					let namePart = email.split('@')[0];
					$('#name').val(namePart);
				}
			});
		});
	</script>

@endpush
