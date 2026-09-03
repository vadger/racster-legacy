@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-xl-10" id="registerForm">

			@if (Auth::user()->hasRole('admin'))

				@if (count($errors) > 999) <div class="alert alert-danger"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
				@if(Session::has('notice')) <div class="alert alert-danger text-center"> {!! Session::get('notice') !!} </div> @endif
				@if(Session::has('message')) <div class="alert alert-success text-center"> {!! Session::get('message') !!} </div> @endif

				<!-- Search filters (stateless, GET) -->
				<form class="form-horizontal" role="form" method="GET" action="{{ LaravelLocalization::localizeUrl('/users') }}" id="users-search-form">
					<div class="card shadow rounded mb-3">
						<div class="card-body" id="userssearch">
							<div class="row g-2 align-items-center">
								<div class="col-lg-3 col-md-6 col-12">
									<input class="form-control form-control-sm" type="text" name="name" id="search_name" value="{{ $filters['name'] }}" autocomplete="off" placeholder="@lang('racster.search-by-name')" />
								</div>
								<div class="col-lg-3 col-md-6 col-12">
									<input class="form-control form-control-sm" type="text" name="email" id="search_email" value="{{ $filters['email'] }}" autocomplete="off" placeholder="@lang('racster.search-by-email')" />
								</div>
								<div class="col-lg-2 col-md-6 col-12">
									<input class="form-control form-control-sm" type="text" name="phone" id="search_phone" value="{{ $filters['phone'] }}" autocomplete="off" placeholder="@lang('racster.search-by-phone')" />
								</div>
								<div class="col-lg-2 col-md-6 col-12">
									<select class="form-select form-select-sm" name="role" id="search_role">
										<option value="">@lang('racster.search-all-roles')</option>
@if (!empty($roles))
@foreach ($roles as $rkey => $rname)
										<option value="{{ $rkey }}" @if ((string) $filters['role'] === (string) $rkey) selected @endif>{{ ucfirst($rname) }}</option>
@endforeach
@endif
									</select>
								</div>
								<div class="col-lg-2 col-md-12 col-12">
									<div class="input-group input-group-sm">
										<button type="submit" class="btn btn-primary">@lang('racster.search')</button>
										<a href="{{ LaravelLocalization::localizeUrl('/users') }}" class="btn btn-secondary">@lang('racster.reset')</a>
									</div>
								</div>
							</div>
							<div class="row mt-1">
								<div class="col-12">
									<span class="small text-secondary">@lang('racster.search-wildcard-hint')</span>
								</div>
							</div>
						</div>
					</div>
				</form>

				<form class="form-horizontal" role="form" method="POST" action="{{ LaravelLocalization::localizeUrl('/users'.((!empty($cngrole) && !empty($roles[$cngrole])) ? '/'.$cngrole : '')) }}">
					{{ csrf_field() }}

					<div class="card shadow rounded">
						<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
							<span>@lang('racster.manage-users-title')</span>
						</div>
						<div class="card-body" id="listusers">

@if (!empty(config('racster.superadmin')) && in_array(Auth::user()->id, config('racster.superadmin')))
							<div class="row mb-2">
								<div class="col-lg-4 col-md-6 col-12">
									<div class="input-group input-group-sm">
										<input class="form-control text-center" @if (!empty($cngrole) && !empty($roles[$cngrole])) value="{{ $roles[$cngrole] }}" @endif type="text" name="user_role" id="user_role" autocomplete="off" placeholder="@lang('racster.role-name')" />
										<button type="submit" name="addnewrole" value="Yes" class="btn btn-primary">
											@if (!empty($cngrole) && !empty($roles[$cngrole])) @lang('racster.change') @else @lang('racster.add-new') @endif
										</button>
									</div>
								</div>
							</div>
@endif

							<div class="row mb-2">
								<div class="col-lg-12">
@if (!empty($urows))
									<ul class="list-group list-group-flush" id="userlist">
@foreach ($urows as $user)
@if (!empty($user->user_roles) && $user->id != Auth::user()->id)
@php
	$user_roles = [];
	foreach(explode('|', $user->user_roles) as $rname){
		$user_roles[] = 'urole'.$rname;
	}
@endphp
@endif
										<li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center px-0 pt-0 pb-md-0{{ (!empty($user_roles) ? ' '.implode(' ', $user_roles) : '') }}">
											<label class="checkbox flex-fill py-2" role="button">
												<input type="checkbox" name="roleinfo[]" value="{{ $user->id }}" @if ($user->id == Auth::user()->id) disabled @endif />
@if (!empty($user->first_name))
												{{ ucfirst($user->first_name).(!empty($user->last_name) ? ' '.ucfirst($user->last_name) : '') }}
@else
												{{ ucfirst($user->name) }}
@endif
												<span class="small text-secondary fst-italic">{{ $user->email }} | {{ $user->mobile_country_code.$user->mobile_number }}</span>
											</label>
											<div class="d-flex flex-wrap flex-md-row justify-content-between gap-1">
@if (!empty($user->user_roles))
												<div class="d-flex flex-wrap gap-1">
@foreach(explode('|', $user->user_roles) as $rname)
@if (!empty($roles[$rname]))
@if ($user->id == Auth::user()->id)
													<span class="btn btn-light btn-sm text-nowrap">
@else
													<a href="{{ LaravelLocalization::localizeUrl('/remove-role/'.$user->id.'/'.$rname) }}" class="btn btn-danger btn-sm text-nowrap remove-role">
@endif
														{{ $roles[$rname] }}
@if ($user->id == Auth::user()->id)
													</span>
@else
														<i class="fas fa-trash-alt"></i>
													</a>
@endif
@endif
@endforeach
												</div>
												<div class="d-flex gap-1 ms-auto">
@if (in_array(config('racster.def_role'), explode('|', $user->user_roles)))
													<a href="{{ route('users-view-credit', ['uid' => $user->id]) }}" class="btn btn-sm btn-info edit-usercredit">
														@lang('racster.manage-users-credit')
													</a>
@endif
													<a href="{{ route('users-show-info', ['uid' => $user->id]) }}" class="btn btn-sm btn-secondary edit-userdata">
@if (in_array(config('racster.coaches_role'), explode('|', $user->user_roles)))
														@lang('racster.manage-users-coach')
@else
														@lang('racster.manage-users-settings')
@endif
													</a>
												</div>
@endif
											</div>
										</li>
@endforeach
									</ul>
@endif
								</div>
							</div>

							<div class="form-group row mb-0 mt-3">
								<div class="col-12">
									<div class="def-pagi text-center">{{ $urows->total() }} @lang("racster.users") {{ $urows->onEachSide(2)->links() }}</div>
								</div>
							</div>

						</div>
						<div class="card-footer d-flex flex-column flex-lg-row justify-content-between align-items-center gap-1">

							<div class="text-center">
								<div class="input-group">
									<select class="form-select" name="role_type" id="role_type">
										<option value=""> --- --- --- </option>
@if (!empty($roles))
@foreach ($roles as $rkey => $role)
										<option value="{{ $rkey }}">{{ ucfirst($role) }}</option>
@endforeach
@endif
									</select>
									<button type="submit" name="roletouser" value="Yes" class="btn btn-primary">
										@lang('racster.add-role')
									</button>
								</div>
							</div>

							<div class="text-center">
								<button type="submit" name="removeuser" value="Yes" class="btn btn-secondary" id="delaction">
									@lang('racster.delete-selected-users')
								</button>
							</div>

						</div>
					</div>

				</form>

			@endif

		</div>
	</div>

@endsection

@if (Auth::user()->hasRole('admin'))
@push("custom_scripts")
	<script type="text/javascript">
		$(document).ready(function (){
			$('.alert-danger').delay(5000).fadeOut('slow');
			$('.alert-success').not('.collapse').delay(3000).fadeOut('slow');
		});
		$('#delaction, a.remove-role, a.delete-role').on('click', function (e) {
			if (confirm(($(this).attr('id') ? "@lang('racster.sure-to-delete-users')" : "@lang('racster.sure-to-remove-user-role')"))) {
				return true;
			} else {
				e.preventDefault();
				return false;
			}
		});
	</script>
@endpush
@endif
