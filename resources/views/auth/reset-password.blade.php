@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
	<div class="col-md-6">
		<div class="p-4 bg-white shadow rounded">

			<h2 class="mb-4">@lang('auth.reset-password-page-title')</h2>
			<form method="POST" action="{{ route('password.store') }}">
				@csrf
				<input type="hidden" name="token" value="{{ $request->route('token') }}">

				<div class="mb-3">
					<label for="email" class="form-label">@lang('auth.email')</label>
					<input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $request->email) }}" required autofocus>
					@error('email')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>

				<div class="mb-3">
					<label for="password" class="form-label">@lang('auth.new-password')</label>
					<input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
					@error('password')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>

				<div class="mb-3">
					<label for="password_confirmation" class="form-label">@lang('auth.confirm-password')</label>
					<input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>
				</div>

				<button type="submit" class="btn btn-success">@lang('auth.reset-password-page-button')</button>
			</form>

		</div>
	</div>
</div>
@endsection
