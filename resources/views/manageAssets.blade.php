@extends('layouts.app')

@section('content')
<div class="container">
	<div class="row justify-content-center">
		<div class="col-xl-10">
			<div class="card shadow rounded">
				<div class="card-header">@lang('assets.header')</div>

				<div class="card-body">

@if (!empty(config('assets.active')))

					<div class="accordion accordion-flush" id="assetsList">

@foreach (config('assets.active') as $akey => $avalue)
@if (empty(config('assets.hidden')) || !in_array($akey, config('assets.hidden')) || Auth::user()->hasrole('admin'))
						<div class="accordion-item bg-white">
							<h2 class="accordion-header d-flex align-items-center" id="asset_{{ $akey }}">
								<div class="me-2 ps-2">
									<a href="#" class="btn btn-sm btn-secondary manage-asset" data-bs-target=".manage-asset-modal" data-bs-toggle="modal" data-atype="{{ $akey }}">
										<i class="fas fa-plus"></i>
									</a>
								</div>
								<button class="accordion-button{{ ((Session::has('active-asset-row') && Session::get('active-asset-row') == $akey) ? '' : ' collapsed') }} justify-content-between" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_{{ $akey }}" aria-expanded="{{ ((Session::has('active-asset-row') && Session::get('active-asset-row') == $akey) ? 'true' : 'false') }}" aria-controls="collapse_{{ $akey }}">
									@lang('assets.asset-title-'.$akey)
								</button>
							</h2>
							<div id="collapse_{{ $akey }}" class="accordion-collapse collapse{{ ((Session::has('active-asset-row') && Session::get('active-asset-row') == $akey) ? ' show' : '') }}" aria-labelledby="asset_{{ $akey }}" data-bs-parent="#assetsList">
								<div class="accordion-body ms-5 py-1 ps-1">
@if (!empty($assets) && !empty($assets['bytype'][$akey]))
									<ul class="list-group list-group-flush">
@foreach ($assets['bytype'][$akey] as $asset)
									<li class="list-group-item d-flex flex-md-row flex-column justify-content-between align-items-md-center">
										<div class="mr-auto">
@if (!empty($asset->parent))
											<strong>{{ ((!empty($assets['byid']) && array_key_exists($asset->parent, $assets['byid'])) ? $assets['byid'][$asset->parent]->title : $asset->parent) }}: </strong>
@endif
											{{ $asset->title }}
@if (!empty($asset->descr))
											<i class="small text-secondary">({{ $asset->descr }})</i>
@endif
										</div>
										<div class="text-end">
@if ($asset->type == 'userrole' && in_array($asset->id, config('racster.lockroles')))
											<i class="fas fa-user-lock text-secondary"></i>
@elseif ($asset->type == 'work-status' && !in_array(Auth::user()->id, config('racster.superadmin')))
											<i class="fas fa-lock text-secondary"></i>
@else
											<a href="#" class="btn btn-sm btn-secondary manage-asset" data-bs-target=".manage-asset-modal" data-bs-toggle="modal" data-atype="{{ $asset->type }}" data-aid="{{ $asset->id }}">
												@lang('assets.change')
											</a>
@if (empty(config('assets.hidden')) || !in_array($akey, config('assets.hidden')) || in_array(Auth::user()->id, config('racster.superadmin')))
											<a href="#" class="btn btn-sm btn-secondary ms-1 delete-asset" data-aid="{{ $asset->id }}">
												@lang('assets.delete')
											</a>
@endif
@endif
@if (!empty($asset->extra))
											<small class="ps-2 text-secondary">{{ $asset->extra }} <i class="fas fa-sort-amount-down"></i></small>
@endif
										</div>
									</li>
@endforeach
									</ul>
@else
									<div class="p-2">
										@lang('assets.no-assets-found')
									</div>
@endif
								</div>
							</div>
						</div>
@endif
@endforeach

					</div>

					<div class="modal fade manage-asset-modal" tabindex="-1" role="dialog" aria-labelledby="@lang('assets.manage-asset')">
						<div class="modal-dialog">
							<div class="modal-content">

								<div class="modal-header pb-1">
									<h4 class="modal-title">
										@lang('assets.modal-title-manage-asset')
									</h4>
									<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('assets.modal-close-window')"></button>
								</div>

								<div class="modal-body text-left p-3">

									<div class="row mb-2 collapse" id="asset_parent_wrap">
										<label class="col-sm-4 col-form-label text-sm-end" for="asset_parent" data-bs-title="@lang('assets.asset-parent')">
											<span>@lang('assets.asset-parent')</span>*
										</label>
										<div class="col-sm-8">
											<select class="form-select" name="asset_parent" id="asset_parent">
												<option value="">@lang('assets.select-parent')</option>
											</select>
										</div>
									</div>

									<div class="row mb-2 collapse" id="asset_title_wrap">
										<label class="col-sm-4 col-form-label text-sm-end" for="asset_title" data-bs-title="@lang('assets.asset-title')">
											<span>@lang('assets.asset-title')</span>*
										</label>
										<div class="col-sm-8">
											<input class="form-control" value="" type="text" name="asset_title" id="asset_title" autocomplete="off" autofocus />
										</div>
									</div>

@if (!empty(LaravelLocalization::getSupportedLocales()))
@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
@if ($localeCode != 'et')
									<div class="row mb-2 collapse asset_title_trans" id="asset_title_trans_{{ $localeCode }}_wrap">
										<div class="col-sm-4 p-1 text-sm-end">
											<img src="{{ asset('/images/'.$localeCode.'.png') }}" border="0" style="height:20px;" />
										</div>
										<div class="col-sm-8">
											<input class="form-control" value="" type="text" name="title_trans[{{ $localeCode }}]" id="title_trans_{{ $localeCode }}" autocomplete="off" />
										</div>
									</div>
@endif
@endforeach
@endif

									<div class="row mb-2 collapse" id="asset_descr_wrap">
										<label class="col-sm-4 col-form-label text-sm-end" for="asset_descr" data-bs-title="@lang('assets.asset-description')">
											<span>@lang('assets.asset-description')</span>
										</label>
										<div class="col-sm-8">
											<textarea class="form-control" rows="4" name="asset_descr" id="asset_descr"></textarea>
										</div>
									</div>

@if (!empty(LaravelLocalization::getSupportedLocales()))
@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
@if ($localeCode != 'et')
									<div class="row mb-2 collapse asset_descr_trans" id="asset_descr_trans_{{ $localeCode }}_wrap">
										<div class="col-sm-4 p-1 text-sm-end">
											<img src="{{ asset('/images/'.$localeCode.'.png') }}" border="0" style="height:20px;" />
										</div>
										<div class="col-sm-8">
											<textarea class="form-control" rows="4" name="descr_trans[{{ $localeCode }}]" id="descr_trans_{{ $localeCode }}"></textarea>
										</div>
									</div>
@endif
@endforeach
@endif

									<div class="row collapse" id="asset_extra_wrap">
										<label class="col-sm-4 col-form-label text-sm-end" for="asset_extra" data-bs-title="@lang('assets.asset-order-no')">
											<span>@lang('assets.asset-order-no')</span>*
										</label>
										<div class="col-sm-8">
											<input class="form-control" value="" type="number" name="asset_extra" id="asset_extra" min="0" autocomplete="off" />
										</div>
									</div>

									<input type="hidden" name="asset_type" id="asset_type" value="" />
									<input type="hidden" name="asset_id" id="asset_id" value="" />

								</div>

								<div class="modal-footer d-flex justify-content-between">
									<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
										@lang('assets.modal-close-window')
									</button>
									<button type="button" class="btn btn-primary" id="saveAsset">
										@lang('assets.modal-save-changes')
									</button>
								</div>

							</div>
						</div>
					</div>

@else
					@lang('assets.no-assets-defined')
@endif

				</div>

			</div>
		</div>
	</div>
</div>
@endsection

@push("custom_scripts")
	<script type="text/javascript">
		$(document).on('click', '.accordion-header > button', function(){
			var sel = $(this).closest('.accordion-header').attr('id').replace('asset_', '');
			$.ajax({
				type: "POST",
				url: "{{ LaravelLocalization::localizeUrl('/activeAsset') }}",
				data: {
					'row': sel,
					'_token': '{{ csrf_token() }}'
				}
			});
		});
		$(".manage-asset-modal input, .manage-asset-modal select").keypress(function(e) {
			if (e.which === 13) {
				e.stopPropagation(); e.preventDefault();
				$('#saveAsset').click();
			}
		});
		$(document).on('click', '.manage-asset', function(e){
			var _this = $(this), atype = _this.data('atype'), aid = _this.data('aid'), modal = $(_this.data('bs-target'));
			e.preventDefault(); e.stopPropagation();
			modal.find('.modal-body > .alert').remove();
			modal.find('.modal-body .is-invalid').removeClass('is-invalid');
			modal.find('.modal-body .has-error').removeClass('has-error');
			modal.find('.modal-body > .row').removeClass('show');
			$.ajax({
				type: "POST",
				url: "{{ LaravelLocalization::localizeUrl('/manageAsset') }}",
				data: {
					'type': atype,
					'aid': aid,
					'_token': '{{ csrf_token() }}'
				},
				success: function(info){
					if (info.success){
						modal.find('.modal-body input[name="asset_type"]').val(info.type);
						modal.find('.modal-body input[name="asset_id"]').val(info.id);
						$.each(info.asset, function(field, name){
							var field_label = modal.find('.modal-body #asset_' + field + '_wrap > label');
							if (field == 'parent' && info.asset_parents){
								var select_html = '<option value="">@lang("assets.select-parent")</option>';
								$.each(info.asset_parents, function(id, value){
									select_html += '<option value="' + id + '">' + value + '</option>';
								});
								modal.find('.modal-body #asset_' + field).html(select_html);
							}
							modal.find('.modal-body #asset_' + field).val(((!aid && field == 'extra') ? 10 : info.data[field]));
							modal.find('.modal-body #asset_' + field + '_wrap').addClass('show');
							if ((field == 'title' || field == 'descr') && info.trans){
								$.each(info.trans, function(lang, value){
									if (value){
										$.each(value, function(k,v){
											if (k == field){
												modal.find('.modal-body [name="' + field + '_trans[' + lang + ']"]').val(v);
											}
										});
									}else{
										modal.find('.modal-body [name="' + field + '_trans[' + lang + ']"]').val('');
									}
								});
								modal.find('.modal-body .asset_' + field + '_trans').addClass('show');
							}
							field_label.find('> span').text((info.titles[field] != '' ? info.titles[field] : field_label.data('title')));
						});
						setTimeout(function(){
							modal.find('.modal-body #asset_title_wrap label').trigger('click');
						}, 500);
					}else{
						modal.find('.modal-body > .alert').remove();
						modal.find('.modal-body').prepend('<div class="alert alert-danger text-center">' + info.msg + '</div>');
						modal.find('.modal-body > .alert-danger').first().delay(5000).fadeOut('slow', function(){ $(this).remove(); });
					}
				}
			});
		});
		$(document).on('click', '#saveAsset', function(e){
			var btn = $(this), modal = btn.closest('.modal'), _trans = {};
			if (confirm("{{ trans('assets.sure-you-want-to-save-the-asset') }}")) {
				modal.find('.modal-body > .alert').remove();
				modal.find('.modal-body .is-invalid').removeClass('is-invalid');
				modal.find('.modal-body .has-error').removeClass('has-error');
				$.each($('.asset_title_trans.show input, .asset_descr_trans.show textarea', modal),function(k, v){
					_trans[$(this).attr('id').replace('trans_', '')] = v.value;
				});
				$.ajax({
					type: "POST",
					url: "{{ LaravelLocalization::localizeUrl('/saveAsset') }}",
					data: {
						'type': $('.manage-asset-modal #asset_type').val(),
						'aid': $('.manage-asset-modal #asset_id').val(),
						'parent': $('.manage-asset-modal #asset_parent').val(),
						'title': $('.manage-asset-modal #asset_title').val(),
						'descr': $('.manage-asset-modal #asset_descr').val(),
						'extra': $('.manage-asset-modal #asset_extra').val(),
						'trans': _trans,
						'_token': '{{ csrf_token() }}'
					},
					success: function(info){
						if (info.success){
							window.location.reload();
						}else{
							if (info.errors){
								modal.find('.modal-body .is-invalid').removeClass('is-invalid');
								modal.find('.modal-body .has-error').removeClass('has-error');
								$.each(info.errors, function(fieldName, error){
									modal.find('[name^="asset_' + fieldName + '"]').addClass('is-invalid');
									modal.find('[name^="asset_' + fieldName + '"]').parent().addClass('has-error');
								});
							}
							modal.find('.modal-body > .alert').remove();
							modal.find('.modal-body').prepend('<div class="alert alert-danger text-center">' + info.msg + '</div>');
							modal.find('.modal-body > .alert-danger').first().delay(5000).fadeOut('slow', function(){ $(this).remove(); });
							$('html, body').animate({ scrollTop: $('#assetsList').offset().top - 80 });
						}
					}
				});
				return true;
			} else { return false; }
		});
		$(document).on('click', '.delete-asset', function(e){
			var asset = $(this);
			e.preventDefault(); e.stopPropagation();
			if (confirm("{{ trans('assets.sure-you-want-to-remove-the-asset') }}")) {
				$.ajax({
					type: "POST",
					url: "{{ LaravelLocalization::localizeUrl('/deleteAsset') }}",
					data: {
						'aid': asset.data('aid'),
						'_token': '{{ csrf_token() }}'
					},
					success: function(info){
						if (info.success){
							asset.closest('li').remove();
						}
						modal.find('.modal-body > .alert').remove();
						modal.find('.modal-body').prepend('<div class="alert alert-' + (info.success ? 'success' : 'danger') + ' text-center">' + info.msg + '</div>');
						modal.find('.modal-body > .alert-' + (info.success ? 'success' : 'danger')).first().delay(5000).fadeOut('slow', function(){ $(this).remove(); });
					}
				});
				return true;
			} else { return false; }
		});
	</script>
@endpush
