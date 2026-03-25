@extends('layouts.app')

@section('content')
<div class="container">
	<div class="row justify-content-center">
		<div class="col-12">
			<div class="card">

				<div class="card-body">
@if(Session::has('notice'))
					<div class="alert alert-danger text-center" role="alert">
						{{ session('notice') }}
					</div>
@else
@if (session('status'))
					<div class="alert alert-success" role="alert">
						{{ session('status') }}
					</div>
@endif

					@lang('racster.no-user-text')
@endif
				</div>

			</div>
		</div>
	</div>
</div>
@endsection
