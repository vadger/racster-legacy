@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
	<div class="col-md-6">
		<div class="p-4 bg-white shadow rounded">

			<h2 class="mb-4">@lang('auth.forgot-password-title')</h2>

			@if (session('status'))
				<div class="alert alert-primary mb-2 p-2 small text-center">
					{{ session('status') }}
				</div>
			@endif

			<form method="POST" action="{{ route('password.email') }}">
				@csrf
				<div class="mb-3">
					<label for="email" class="form-label">@lang('auth.email')</label>
					<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
					@error('email')
						<div class="invalid-feedback">{{ $message }}</div>
					@enderror
				</div>
				<button type="submit" class="btn btn-primary">@lang('auth.forgot-password-button')</button>
			</form>

		</div>
	</div>
</div>
@endsection
