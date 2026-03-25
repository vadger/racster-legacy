<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Validator;
use Session;
use Lang;
use Response;
use LaravelLocalization;
use \App\Models\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssetsController extends Controller
{

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {

		$this->thistime = time();
		$this->perlist = 20;

    }

    /**
     * Change assets view active asset status
     */
	public function manageActiveAsset(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('manager') and !empty($request->input('row'))){

			if (Session::has('active-asset-row') and $request->input('row') == Session::get('active-asset-row')){

				Session::forget('active-asset-row');

			}else{

				Session::put('active-asset-row', $request->input('row'));

			}

			return response()->json([
				'success' => true,
				'msg' => trans('assets.active-asset-row-updated')
			]);

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op')
		]);

	}

    /**
     * Show assets list
     */
    public function showAssetsList()
	{

		// Define assets array
		$asset_list = [];

		// Get all assets
		$assets = DB::table('racster_assets')
			->whereIn('type', array_keys(config('assets.active')))
			->whereNull('deleted_at')
			->orderByRaw("CAST(extra AS UNSIGNED)")
			->orderBy('title', 'asc')
			->get();

		// Create predefined arrays of assets
		foreach ($assets as $asset){
			$asset_list['bytype'][$asset->type][] = $asset;
			$asset_list['byid'][$asset->id] = $asset;
		}

		return view('manageAssets', [
			'assets' => $asset_list,
		]);

	}

    /**
     * Get asset data
     */
    public function manageAssetData(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('manager')){
			
			if (!empty($request->input('type')) and array_key_exists($request->input('type'), config('assets.active'))){

				$titles = [];

				foreach (config('assets.active.'.$request->input('type')) as $akey => $title){

					// Define custom field titles
					$titles[$akey] = (!empty($title) ? trans('assets.field-title-'.$title) : '');

					// Get all parents
					if ($akey == 'parent'){

						switch ($request->input('type')){
							case 'entry-length':
							case 'entry-price':
							case 'entry-minperiod':
							case 'entry-mincancel':
								$parents = DB::table('racster_assets')
									->where('type', $title)
									->whereNull('deleted_at')
									->orderByRaw("CAST(extra AS UNSIGNED)")
									->orderBy('title', 'asc')
									->pluck('title', 'id')
									->toArray();
							break;
						}

					}

				}

				// Get existing asset data
				if (!empty($request->input('aid'))){

					$data = DB::table('racster_assets')
						->where('id', $request->input('aid'))
						->where('type', $request->input('type'))
						->whereNull('deleted_at')
						->first();

				}

				// Define translations
				if (!empty(LaravelLocalization::getSupportedLocales()) and in_array($request->input('type'), config('assets.translated_assets'))){

					$trans = [];

					foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties){

						$trans[$localeCode] = DB::table('racster_assets')
							->select('title', 'descr')
							->where('parent', $request->input('aid'))
							->where('type', 'asset-translation')
							->where('extra', $localeCode)
							->whereNull('deleted_at')
							->first();

					}

				}

				return response()->json([
					'success' => true,
					'type' => $request->input('type'),					
					'asset' => config('assets.active.'.$request->input('type')),
					'titles' => $titles,
					'asset_parents' => (!empty($parents) ? $parents : ''),
					'data' => (!empty($data) ? $data : ''),
					'trans' => ((!empty(LaravelLocalization::getSupportedLocales()) and in_array($request->input('type'), config('assets.translated_assets')) and !empty($trans)) ? $trans : ''),
					'id' => (!empty($request->input('aid')) ? $request->input('aid') : ''),
				]);

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op')
		]);

	}

    /**
     * Insert / update asset data
     */
    public function saveAssetData(Request $request)
	{

		if (request()->ajax() and Auth::user()->hasRole('manager')){
			
			if (!empty($request->input('type')) and array_key_exists($request->input('type'), config('assets.active'))){

				$fields = []; $validation_rules = [];

				foreach (config('assets.active.'.$request->input('type')) as $field => $title){
					$fields[$field] = $request->input($field);
					$validation_rules[$field] = ($field != 'descr' ? 'required|max:255' : '');
				}

				$validator = Validator::make($fields, $validation_rules); // Validate input rules

				if (empty($fields) or $validator->fails()){ // If main validation fails

					return response()->json([
						'success' => false,
						'msg' => trans('racster.all-fields-are-required'),
						'errors' => $validator->getMessageBag()->toArray(),
					]);

				}

				// Get existing asset data
				if (!empty($request->input('aid'))){

					DB::table('racster_assets')
						->where('id', $request->input('aid'))
						->update([
							'uid'			=> Auth::user()->id,
							'parent'		=> (!empty($fields['parent']) ? $fields['parent'] : NULL),
							'title'			=> $fields['title'],
							'descr'			=> (isset($fields['descr']) ? $fields['descr'] : NULL),
							'extra'			=> (!empty($fields['extra']) ? $fields['extra'] : 10),
							'updated_at'	=> \Carbon\Carbon::now()
						]);

				}else{

					$asset_id = DB::table('racster_assets')->insertGetId([
						'uid'			=> Auth::user()->id,
						'type'			=> $request->input('type'),
						'parent'		=> (!empty($fields['parent']) ? $fields['parent'] : NULL),
						'title'			=> $fields['title'],
						'descr'			=> (isset($fields['descr']) ? $fields['descr'] : NULL),
						'extra'			=> (!empty($fields['extra']) ? $fields['extra'] : 10),
						'created_at'	=> \Carbon\Carbon::now(),
						'updated_at'	=> \Carbon\Carbon::now()
					]);

				}

				// Save asset translations if they are added
				if (!empty(LaravelLocalization::getSupportedLocales()) and in_array($request->input('type'), config('assets.translated_assets')) and !empty($request->input('trans'))){

					$translations = [];

					// Create array of translations
					foreach ($request->input('trans') as $field => $translation){

						// Define translation language
						$lang = substr(strstr($field, '_'), 1);

						// Add translation to translations array
						$translations[$lang][strstr($field, '_', true)] = $translation;

						// Check if translation exists
						$translation_id = DB::table('racster_assets')
							->where('type', 'asset-translation')
							->where('parent', (!empty($asset_id) ? $asset_id : $request->input('aid')))
							->where('extra', $lang)
							->whereNull('deleted_at')
							->value('id');

						// Add translation exists add the ID to translations array
						$translations[$lang]['id'] = (!empty($translation_id) ? $translation_id : NULL);

					}

					if (!empty($translations)){

						foreach ($translations as $lang => $translation){

							// Check if translation is entered
							if (isset($translation['title']) or isset($translation['descr'])){

								if (empty($translation['id'])){

									DB::table('racster_assets')->insertGetId([
										'uid'			=> Auth::user()->id,
										'type'			=> 'asset-translation',
										'title'			=> (isset($translation['title']) ? $translation['title'] : ''),
										'descr'			=> (isset($translation['descr']) ? $translation['descr'] : null),
										'extra'			=> $lang,
										'parent'		=> (!empty($asset_id) ? $asset_id : $request->input('aid')),
										'created_at'	=> \Carbon\Carbon::now(),
										'updated_at'	=> \Carbon\Carbon::now()
									]);

								}else{

									DB::table('racster_assets')->where('id', $translation['id'])->update([
										'uid'			=> Auth::user()->id,
										'title'			=> (isset($translation['title']) ? $translation['title'] : ''),
										'descr'			=> (isset($translation['descr']) ? $translation['descr'] : null),
										'updated_at'	=> \Carbon\Carbon::now()
									]);

								}

							}elseif (!empty($translation['id'])){

								// Remove existing translation
								DB::table('racster_assets')
									->where('id', $translation['id'])
									->update([
										'deleted_at' => \Carbon\Carbon::now(),
									]);

							}

						}

					}

				}

				return response()->json([
					'success' => true,
					'aid' => (!empty($request->input('aid')) ? $request->input('aid') : $asset_id),
				]);

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op')
		]);

	}

    /**
     * Remove asset
     */
    public function removeAsset(Request $request)
    {

		if (request()->ajax() and Auth::user()->hasRole('manager') and !empty($request->input('aid'))){

			// Get asset data
			$asset_id = DB::table('racster_assets')
				->where('id', $request->input('aid'))
				->whereNull('deleted_at')
				->value('id');

			if (!empty($asset_id)){

				// Mark application note as deleted
				DB::table('racster_assets')
					->where('id', $asset_id)
					->update([
						'deleted_at' => \Carbon\Carbon::now()
					]);

				return response()->json([
					'success' => true,
					'msg' => trans('assets.asset-successfully-removed')
				]);

			}

		}

		return response()->json([
			'success' => false,
			'msg' => trans('racster.no-rights-for-op')
		]);

	}

}
