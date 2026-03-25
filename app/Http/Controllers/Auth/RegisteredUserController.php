<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {

		\App\Models\User::where('email', $request->email)
			->whereNull('email_verified_at')
			->delete();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
				'required', 'string', 'lowercase', 'email', 'max:255',
				Rule::unique('users')->where(function($q){
					$q->whereNull('deleted_at')
						->whereNotNull('email_verified_at');
				})
			],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
			'user_roles' => (!empty(config('racster.def_role')) ? config('racster.def_role') : NULL),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));

    }

}
