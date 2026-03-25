<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>{{ config('app.name', 'Laravel') }}</title>
	<link rel="shortcut icon" type="image/png" href="{{ asset('images/r-favicon.png') }}"/>
	<meta name='robots' content='noindex, nofollow' />


	<!-- Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
	<script src="{{ asset('js/jquery-ui-1.13.2.min.js') }}" defer></script>
	<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">

	<!-- Styles -->
	@vite(['resources/css/app.scss', 'resources/js/app.js'])
	<link rel="stylesheet" href="{{ asset('js/jquery-ui-1.13.2.css') }}">
	<link href="{{ asset('css/racster_styles.css') }}?v={{ filemtime(public_path('css/racster_styles.css')) }}" rel="stylesheet">

</head>
<body class="lang-{{ app()->getLocale() }}">

	@include('layouts.navigation')

	<main class="container py-2">
		@yield('content')
	</main>

	<!-- Scripts -->
@stack('custom_scripts')

</body>
</html>
