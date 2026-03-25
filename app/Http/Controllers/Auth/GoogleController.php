<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

		$user = User::where('google_id', $googleUser->getId())
			->orWhere('email', $googleUser->getEmail())
			->first();

		if (!$user){

			// Pull raw google data
			$raw = $googleUser->user;

			// inside your Socialite callback
			if ($avatar = $raw['picture'] ?? null) {
				// build a unique filename
				$filename = 'profile_images/' . Str::random(40) . '.jpg';

				// write the file to storage/app/public/avatars/...
				Storage::disk('public')->put($filename, file_get_contents($avatar));

				// now $filename is what you want to save
				$data['profile_image'] = $filename;
			}

			// then:
			$user = User::updateOrCreate(
				['email' => $googleUser->getEmail()],
				array_merge($data, [
					'name'              => (!empty($googleUser->getName()) ? $googleUser->getName() : $googleUser->getNickname()),
					'email_verified_at' => now(),
					'google_id'         => $googleUser->getId(),
					'password'          => bcrypt(Str::random(24)),
					'first_name'        => $raw['given_name'] ?? null,
					'last_name'         => $raw['family_name'] ?? null,
					'user_roles'        => (!empty(config('racster.def_role')) ? config('racster.def_role') : NULL),
				])
			);

		}

        Auth::login($user);

        return redirect()->intended('/home');
    }
}
