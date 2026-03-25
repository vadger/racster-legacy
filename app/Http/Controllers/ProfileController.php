<?php

namespace App\Http\Controllers;

use DB;

use App\Models\RacsterAssets;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{

	/**
	 * Display the user's profile form.
	 */
	public function edit(Request $request): View
	{

		// Get client levels that are grouping other levels
		$groupLevels = array_values(array_unique(array_merge(...config('racster.client-levels', []))));

		$levels = RacsterAssets::query()
			->from('racster_assets as a')
			->leftJoin('racster_assets as t', function ($join) {
				$join->on('t.parent', '=', 'a.id')
					->where('t.type', '=', 'asset-translation')
					->where('t.extra', '=', app()->getLocale())
					->whereNull('t.deleted_at');
			})
			->where('a.type', 'client-level')
			->whereNotIn('a.id', $groupLevels)
			->whereNull('a.deleted_at')
			->orderByRaw('CAST(a.extra AS UNSIGNED) ASC')
			->get([
				'a.id',
				DB::raw('COALESCE(t.title, a.title) as title'),
				DB::raw('COALESCE(t.descr, a.descr) as descr'),
			]);

		return view('profile.edit', [
			'user' => $request->user(),
			'levels' => $levels,
		]);

	}

	/**
	 * Update the user's profile information.
	 */
	public function update(ProfileUpdateRequest $request): RedirectResponse
	{

		$user = $request->user();

		// Handle image upload manually
		$data = $request->validated();

		if ($request->hasFile('profile_image')) {
			$data['profile_image'] = $request->file('profile_image')->store('profile_images', 'public');
		}

		$user->fill($data);

		if ($request->user()->isDirty('email')) {
			$request->user()->email_verified_at = null;
		}

		$request->user()->save();

		return Redirect::route('profile.edit')->with('status', 'profile-updated');

	}

	/**
	 * Delete the user's account.
	 */
	public function destroy(Request $request): RedirectResponse
	{

		$user = $request->user();

		// If this user signed up via Google, skip password check
		if (empty($user->google_id)){
			$request->validateWithBag('userDeletion', [
				'password' => ['required', 'current_password'],
			]);
		}

		Auth::logout();

		$user->delete();

		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return Redirect::to('/');

	}

}
