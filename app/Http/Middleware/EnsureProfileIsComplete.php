<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{

	/**
	 * Handle an incoming request.
	 *
	 * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
	 */
	public function handle(Request $request, Closure $next): Response
	{

		// Only check for authenticated users
		if ($request->user()){

			// Define profile fields that must be filled
			$required = [
				'first_name',
				'last_name',
				'mobile_country_code',
				'mobile_number',
				'birthday',
			];

			if (!$request->user()->hasRole('manager')){
				$required[] = 'user_level';
			}

			// Check if any are null/empty
			$missing = collect($required)
				->filter(fn($field) => empty($request->user()->{$field}))
				->isNotEmpty();

			// If missing and not already on profile routes, redirect
			if ($missing and ! $request->routeIs(['profile.edit', 'profile.update', 'logout'])) {

				return redirect()
					->route('profile.edit')
					->with('profile_status', trans('racster.profile-must-be-completed'));

			}

		}

		return $next($request);

	}

}
