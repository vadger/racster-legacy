<section class="mb-0">
	<header class="mb-0">
		<h2 class="h5 fw-medium text-dark">
			@lang('racster.delete-account-title')
		</h2>

		<div class="d-flex flex-column flex-md-row align-items-center justify-content-end gap-2">
			<p class="text-muted small">
				@lang('racster.delete-account-description')
			</p>
			<!-- Trigger Button -->
			<button type="button" class="btn btn-secondary text-nowrap" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
				@lang('racster.delete-account-button')
			</button>
		</div>
	</header>

	<!-- Modal -->
	<div class="modal fade @if ($errors->userDeletion->isNotEmpty()) show d-block @endif" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="confirmUserDeletionModalLabel" aria-hidden="true" @if ($errors->userDeletion->isNotEmpty()) style="background-color: rgba(0,0,0,0.5);" @endif>
		<div class="modal-dialog">
			<form method="post" action="{{ route('profile.destroy') }}" class="modal-content">
				@csrf
				@method('delete')

				<div class="modal-header">
					<h5 class="modal-title" id="confirmUserDeletionModalLabel">
						@lang('racster.delete-account-modal-title')
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('racster.close')"></button>
				</div>

				<div class="modal-body">
					<p class="text-muted small mb-3">
						@lang('racster.delete-account-modal-description')
					</p>
@if (empty(Auth::user()->google_id))
					<div class="row align-items-center mb-3">
						<label for="password" class="col-4 mb-0 form-label fw-semibold text-end">@lang('racster.delete-account-modal-password-field-name')</label>
						<div class="col-8">
							<input id="password" name="password" type="password" class="form-control" placeholder="@lang('racster.delete-account-modal-password-field-placeholder')">
@if ($errors->userDeletion->has('password'))
							<div class="text-danger small mt-1">
								{{ $errors->userDeletion->first('password') }}
							</div>
@endif
						</div>
					</div>
@endif
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
						@lang('racster.cancel')
					</button>
					<button type="submit" class="btn btn-danger ms-2">
						@lang('racster.delete-account-modal-confirmation-button')
					</button>
				</div>
			</form>
		</div>
	</div>
</section>
