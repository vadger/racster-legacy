<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<title>{{ config('app.name', 'Laravel') }}</title>
	<link rel="shortcut icon" type="image/png" href="{{ asset('images/r-favicon.png') }}"/>
	<meta name='robots' content='noindex, nofollow' />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	@vite(['resources/css/app.scss', 'resources/js/app.js'])
	<link href="{{ asset('css/racster_quest.css') }}?v={{ filemtime(public_path('css/racster_quest.css')) }}" rel="stylesheet">
</head>
<body>

	<main class="container p-0 d-flex align-items-start justify-content-center racster-guest-page">
		@yield('content')
	</main>

</body>
</html>
