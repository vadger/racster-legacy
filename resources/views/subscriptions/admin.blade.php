@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-xl-10">

			@if (Auth::user()->hasRole('admin'))

				@if (count($errors) > 999) <div class="alert alert-danger main-alert"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
				@if(Session::has('notice')) <div class="alert alert-danger main-alert text-center"> {!! Session::get('notice') !!} </div> @endif
				@if(Session::has('message')) <div class="alert alert-success main-alert text-center"> {!! Session::get('message') !!} </div> @endif

				<div class="card shadow rounded">
					<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
						<span>@lang('stripe-products.users-subscriptions-header')</span>
					</div>
					<div class="card-body">

						<div class="table-responsive">
							<table class="table table-striped align-middle">

							<thead>
								<tr>
									<th>@lang('stripe-products.subscriptions-column-username')</th>
									<th>@lang('stripe-products.subscriptions-column-email')</th>
									<th>@lang('stripe-products.subscriptions-column-stripeid')</th>
									<th>&nbsp;</th>
								</tr>
							</thead>

							<tbody>
@foreach($users as $u)
								<tr>
									<td>{{ ($u->first_name ?? $u->name) }}</td>
									<td>{{ $u->email }}</td>
									<td>{{ $u->stripe_id }}</td>
									<td class="text-end">
										<a href="{{ route('subscriptions.admin.user_list', $u) }}" class="btn btn-sm btn-primary">
											@lang('stripe-products.view-user-subscriptions')
										</a>
									</td>
								</tr>
@endforeach
							</tbody>

							</table>
						</div>

						<div class="form-group row mb-0 mt-3">
							<div class="col-12">
								<div class="def-pagi text-center">{{ $users->total() }} @lang("racster.users") {{ $users->onEachSide(2)->links() }}</div>
							</div>
						</div>

					</div>
				</div>

			@endif

		</div>
	</div>

@endsection

@push("custom_scripts")

	<script type="text/javascript">
		$(document).ready(function ($) {
			$('.main-alert.alert-danger').delay(5000).fadeOut('slow');
			$('.main-alert.alert-success').not('.collapse').delay(3000).fadeOut('slow');
		});
	</script>

@endpush
