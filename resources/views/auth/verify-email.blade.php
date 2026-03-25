@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
	<div class="col-md-8 text-center">
		<div class="p-4 bg-white shadow rounded">

			<h2 class="mb-4">@lang('auth.verify-email-page-title')</h2>

			@if (session('status') === 'verification-link-sent')
				<div class="alert alert-success" role="alert">
					@lang('auth.verification-email-sent')
				</div>
			@endif

			<p>@lang('auth.verify-email-page-description')</p>

			<form method="POST" action="{{ route('verification.send') }}">
				@csrf
				<button type="submit" class="btn btn-primary">@lang('auth.verify-email-page-button')</button>
			</form>

		</div>
	</div>
</div>
@endsection
