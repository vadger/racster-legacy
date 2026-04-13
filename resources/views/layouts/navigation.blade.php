<nav class="navbar navbar-expand-lg navbar-light mb-2">
	<div class="container">

		<a class="navbar-brand" href="{{ LaravelLocalization::localizeUrl(Auth::check() ? '/home' : '/') }}">
			<img src="{{ asset('/images/racster-logo.svg') }}" border="0" class="racsterMainLogo" />
		</a>

@auth

		<div class="ms-auto me-1">
			<a class="btn btn-light" href="{{ LaravelLocalization::localizeUrl(Auth::check() ? '/home' : '/') }}">
				<i class="fas fa-fw fa-btn fa-home d-none d-sm-inline-block"></i> @lang('racster.homepage')
			</a>
@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
@if ($localeCode != config('app.locale'))
			<a class="btn btn-light" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, []) }}">
				@lang('racster.'.$localeCode.'-3code')
			</a>
@endif
@endforeach
		</div>

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="navbar-nav ms-auto">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle d-none d-lg-flex align-items-center gap-1 mb-1 mb-lg-0 p-0" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						<span id="userProfileImage">
							<img src="{{ (Auth::user()->profile_image ? asset('storage/' . Auth::user()->profile_image) : asset('images/user-avatar.png')) }}" />
						</span>
						{{ Auth::user()->first_name ?? Auth::user()->name }}
					</a>
					<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
@if (Auth::user()->hasRole('client') && !Auth::user()->hasRole('manager'))
						<li>
@php
	$balance = \App\Models\UserTransactions::getBalance(auth()->id());
@endphp

							<a class="dropdown-item" href="{{ route('users-view-credit') }}">
								@lang('racster.your-credit', ['balance' => $balance.' '.config('racster.main-currency')])
							</a>

							<span class="dropdown-item">
								@lang('racster.your-level', ['level' => (auth()->user()?->level?->title ?? '-')])
							</span>
							
							<div class="dropdown-divider"></div>
						</li>
@endif
@if (Auth::user()->hasRole('manager'))

						<li>
							<a class="dropdown-item" href="{{ route('assets') }}">
								<i class="fa fa-fw fa-btn fa-cog"></i> @lang('racster.manage-assets')
							</a>
@if (Auth::user()->hasRole('admin'))
							<a class="dropdown-item" href="{{ route('users') }}">
								<i class="fa fa-fw fa-btn fa-users"></i> @lang('racster.manage-users')
							</a>
							<div class="dropdown-divider"></div>
							<a class="dropdown-item" href="{{ route('subscriptions.admin.index') }}">
								<i class="fas fa-fw fa-btn fa-history"></i> @lang('racster.manage-subscriptions')
							</a>
@if (!empty(config('racster.superadmin')) and in_array(Auth::user()->id, config('racster.superadmin')))
							<a class="dropdown-item" href="{{ route('products.index') }}">
								<i class="fa fa-fw fa-btn fa-archive"></i> @lang('racster.manage-products')
							</a>
@endif
@endif
							<div class="dropdown-divider"></div>
						</li>
@endif
@if (Auth::user()->hasRole('client') && !Auth::user()->hasRole('manager'))
						<li>
							<a class="dropdown-item" href="{{ route('users-view-credit') }}">
								<i class="fas fa-fw fa-btn fa-wallet"></i> @lang('racster.my-transactions')
							</a>
							<a class="dropdown-item" href="{{ route('subscriptions.list') }}">
								<i class="fas fa-fw fa-btn fa-credit-card"></i> @lang('racster.my-subscriptions')
							</a>
							<a class="dropdown-item" href="https://billing.stripe.com/p/login/8x24gB2BW0WIgjTdDygrS00" target="_blank">
								<i class="fas fa-fw fa-btn fa-file-invoice"></i> @lang('racster.update-stripe-billing-info')
							</a>
							<div class="dropdown-divider"></div>
						</li>
						<li>
							<a class="dropdown-item" href="{{ route('notifications.list') }}">
								<i class="fas fa-fw fa-btn fa-bell"></i> @lang('racster.my-notifications')
							</a>
							<div class="dropdown-divider"></div>
						</li>
@endif
						<li>
							<a class="dropdown-item" href="{{ route('profile.edit') }}">
								<i class="fas fa-fw fa-btn fa-address-card"></i> @lang('racster.edit-profile')
							</a>
							<div class="dropdown-divider"></div>
						</li>
						<li>
							<form method="POST" action="{{ route('logout') }}">
								@csrf
								<button type="submit" class="dropdown-item">
									<i class="fas fa-sign-out-alt" aria-hidden="true"></i> @lang('racster.logout')
								</button>
							</form>
						</li>
					</ul>
				</li>
			</ul>
		</div>

@else

		<div>
@if (request()->routeIs('password.*'))
			<a class="btn btn-dark" href="{{ LaravelLocalization::localizeUrl('/login') }}">@lang('racster.back')</a>
@else
@if (!request()->routeIs('login'))
			<a class="btn btn-dark" href="{{ LaravelLocalization::localizeUrl('/login') }}">@lang('racster.login')</a>
@endif
@if (!request()->routeIs('register'))
			<a class="btn btn-dark" href="{{ LaravelLocalization::localizeUrl('/register') }}">@lang('racster.register')</a>
@endif
@endif
@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
@if ($localeCode != config('app.locale'))
			<a class="btn btn-light" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, []) }}">
				@lang('racster.'.$localeCode.'-3code')
			</a>
@endif
@endforeach
		</div>

@endauth
	</div>
</nav>
