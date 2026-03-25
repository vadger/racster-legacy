@extends('layouts.app')

@section('content')

@if (session('profile_status'))
	<div class="alert alert-warning statusMessage">
		{{ session('profile_status') }}
	</div>
@endif

	<div class="row gy-4">
		<div class="col-12 col-md-8 col-lg-6 mx-auto">
			<div class="p-4 bg-white shadow rounded">
				@include('profile.partials.update-profile-information-form')
			</div>
		</div>

		<div class="col-12 col-md-8 col-lg-6 mx-auto">
@if (empty(Auth::user()->google_id))
			<div class="mb-4 p-4 bg-white shadow rounded">
				@include('profile.partials.update-password-form')
			</div>
@endif
			<div class="p-4 bg-white shadow rounded">
				@include('profile.partials.delete-user-form')
			</div>
		</div>
	</div>

@endsection

@push("custom_scripts")
	<script>
		window.addEventListener('DOMContentLoaded', function () {
			$(document).ready(function ($){
				setTimeout(function () {
					$('.statusMessage').fadeOut('slow', function () {
						$(this).remove();
					});
				}, 3000);
			});
		});
	</script>
@endpush
