@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-xl-10">

			@if (Auth::user()->hasRole('admin'))

				@if (count($errors) > 999) <div class="alert alert-danger"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
				@if(Session::has('notice')) <div class="alert alert-danger text-center"> {!! Session::get('notice') !!} </div> @endif
				@if(Session::has('message')) <div class="alert alert-success text-center"> {!! Session::get('message') !!} </div> @endif

				<form class="form-horizontal" role="form" method="POST" action="{{ LaravelLocalization::localizeUrl('/users/update-info/'.$user->id) }}">
					{{ csrf_field() }}

					<div class="card shadow rounded">
						<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
							<span class="fw-bold">@lang('racster.manage-user') {{ $user->first_name ?? $user->name }}</span>
							<div class="text-center">
								<a href="{{ LaravelLocalization::localizeUrl('/users') }}" class="btn btn-secondary btn-sm mt-lg-0 mt-1">
									@lang('racster.back-to-users-list')
								</a>
							</div>
						</div>
						<div class="card-body">

@if (in_array(config('racster.coaches_role'), explode('|', $user->user_roles)))

							<div class="form-group row mb-0">
								<div class="col-lg-8 offset-lg-2 mb-1">
									<textarea class="form-control" rows="8" name="user_desc" id="user_desc" placeholder="@lang('racster.coaches-description')">{{ (!empty($user->user_desc) ? $user->user_desc : '') }}</textarea>
								</div>
							</div>

@else

							<div class="form-group row mb-0">
								<div class="col-12 col-md-6 offset-lg-2 col-lg-4">
									<div class="form-group row mb-3">
										<label class="col-6 col-form-label fw-bold" for="user_level">
											@lang('racster.field-client-level')*
										</label>
										<div class="col-6">
											<select class="form-select{{ $errors->has('user_level') ? ' is-invalid' : '' }}" name="user_level" id="user_level">
												<option value="">@lang('racster.select-client-level')</option>
@if (!empty($levels))
@foreach ($levels as $level)
												<option value="{{ $level->id }}"{{ ($user->user_level == $level->id ? ' selected' : '') }}>
													{{ $level->title }}
												</option>
@endforeach
@endif
											</select>
										</div>
									</div>
								</div>
								<div class="col-12 col-md-6 col-lg-4">
									<div class="form-group row mb-3">
										<label class="col-6 col-form-label fw-bold" for="discount_amount">
											@lang('racster.field-discount')*
										</label>
										<div class="col-6">
											<select class="form-select{{ $errors->has('discount_amount') ? ' is-invalid' : '' }}" name="discount_amount" id="discount_amount">
												<option value="">@lang('racster.select-discount-percent')</option>
@for ($discount=0;$discount<=100;$discount++)
												<option value="{{ $discount }}"{{ ($user->discount_amount == $discount ? ' selected' : '') }}>
													{{ $discount }}
												</option>
@endfor
											</select>
										</div>
									</div>
								</div>
							</div>

@endif

						</div>
						<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">

							<div class="ms-lg-auto text-center">
								<button type="submit" class="btn btn-primary form-control">@lang('racster.save-user-info')</button>
							</div>

						</div>
					</div>

			</form>

			@endif

		</div>
	</div>

@endsection
