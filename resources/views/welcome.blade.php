@extends('layouts.guest')

@section('content')
<div class="pt-5 text-center text-white guest-welcome-page">
	<h1 class="display-4 mt-4">@lang('racster.welcome-to-racster')</h1>
	@auth
		<a href="{{ LaravelLocalization::localizeUrl('/home') }}" class="btn btn-primary btn-lg mt-3">
			@lang('racster.go-to-dashboard')
		</a>
	@else
		<a href="{{ route('login') }}" class="btn btn-primary btn-lg mt-3">
			@lang('racster.get-started')
		</a>
	@endauth
</div>
@endsection
