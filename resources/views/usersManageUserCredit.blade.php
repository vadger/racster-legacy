@extends('layouts.app')

@section('content')

	<div class="row justify-content-center">
		<div class="col-xl-10">

			@if (Auth::user()->hasRole('client'))

				@if (count($errors) > 999) <div class="alert alert-danger"> @foreach ($errors->all() as $error) {{ $error }}<br> @endforeach </div> @endif
				@if(Session::has('notice')) <div class="alert alert-danger text-center"> {!! Session::get('notice') !!} </div> @endif
				@if(Session::has('message')) <div class="alert alert-success text-center"> {!! Session::get('message') !!} </div> @endif

				<div class="card shadow rounded">
					<div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
						<span class="fw-bold">
							@lang('racster.manage-user-credit-title')
@if (Auth::user()->hasRole('manager') && !empty($user) && !empty($user->id))
							({{ $user->first_name ?? $user->name }})
@endif
						</span>
					</div>
					<div class="card-body">

@if (Auth::user()->hasRole('manager') && !empty($user) && !empty($user->id))

						<form class="form-horizontal" role="form" method="POST" action="{{ LaravelLocalization::localizeUrl('/users/add-credit/'.$user->id) }}">
							{{ csrf_field() }}

							<div class="form-group row mb-3">
								<div class="col-12 col-md-2 mb-1">
									<input class="form-control{{ $errors->has('amount') ? ' is-invalid' : '' }}" value="{{ (old('amount') ? old('amount') : '') }}" type="number" name="amount" id="amount" min="1" autocomplete="off" placeholder="@lang('racster.transaction-amount-placeholder')" />
								</div>
								<div class="col-12 col-md-8 mb-1">
									<input class="form-control{{ $errors->has('comment') ? ' is-invalid' : '' }}" value="{{ (old('comment') ? old('comment') : '') }}" type="text" name="comment" id="comment" autocomplete="off" placeholder="@lang('racster.transaction-comment-placeholder')" />
								</div>
								<div class="col-12 col-md-2 mb-1">
									<button type="submit" class="btn btn-primary form-control add-credit">@lang('racster.add-credit-to-user')</button>
								</div>
							</div>
						</form>

						<hr />

@endif

@if (!empty($transactions))

							<ul class="list-group list-group-flush">
								@foreach ($transactions as $transaction)
									<li class="list-group-item list-group-item-action list-group-item-secondary">
										<div class="row row-cols-2 row-cols-md-3 align-items-center">
											<div class="col col-md-3 col-xl-2 small">
												<i class="fas fa-user-{{ ($transaction->transaction_type == 'onhold' ? 'clock text-warning' : ($transaction->transaction_type == 'used' ? 'minus text-danger' : 'plus text-success')) }}"></i> {{ date('d.m.Y H:i', strtotime($transaction->created_at)) }}
											</div>
											<div class="col-12 col-md-6 col-xl-8 order-3 small">
												@if (!empty(config('racster.superadmin')) && in_array(Auth::user()->id, config('racster.superadmin')))
													<a href="{{ LaravelLocalization::localizeUrl('/users/delete-credit/'.((!empty($user) && !empty($user->id)) ? $user->id : Auth::user()->id).'/'.$transaction->id) }}" class="btn btn-sm btn-danger py-0 float-end delete-credit">
														@lang('racster.delete-transaction-row')
													</a>
												@endif
@if (!empty($transaction->transaction_comment))
												{{ $transaction->transaction_comment }}
@endif
@if (!empty($transaction->date_id) && !empty($transaction->entry_start))
												({{ date('d.m.Y H:i', strtotime($transaction->entry_start)) }})
@endif
											</div>
											<div class="col col-md-3 col-xl-2 order-md-5 fw-bold text-end">
												{{ round($transaction->transaction_amount, 0).config('racster.transaction-token') }}
											</div>
										</div>
									</li>
								@endforeach
								<li class="list-group-item list-group-item-success">
									<div class="row">
										<div class="col fw-medium">
											@lang('racster.total-amount')
										</div>
										<div class="col fw-bold text-end">
											{{ $balance.config('racster.transaction-token') }}
										</div>
									</div>
								</li>
							</ul>

							<div class="form-group row mb-0 mt-3">
								<div class="col-12">
									<div class="def-pagi text-center">{{ $transactions->total() }} @lang("racster.transactions") {{ $transactions->onEachSide(2)->links() }}</div>
								</div>
							</div>
@else
							<div class="row">
								<div class="col-12">
									@lang('racster.no-transactions-found')
								</div>
							</div>
@endif

						</div>
					</div>

			@endif

		</div>
	</div>

@endsection

@if (Auth::user()->hasRole('manager') && !empty($user) && !empty($user->id))
@push("custom_scripts")

	<script type="text/javascript">

		$('.add-credit').on('click', function (e) {
			if (confirm("@lang('racster.sure-to-add-new-credit')")) {
				return true;
			} else {
				e.preventDefault();
				return false;
			}
		});

		$('.delete-credit').on('click', function (e) {
			if (confirm("@lang('racster.sure-to-delete-the-transaction')")) {
				return true;
			} else {
				e.preventDefault();
				return false;
			}
		});

	</script>

@endpush
@endif
