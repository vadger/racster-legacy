<?php

namespace App\Models;

use DB;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

use App\Notifications\VerifyEmailNotification;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [

		// Add main columns
        'name',
        'email',
		'email_verified_at',
		'google_id',
        'password',

		// Add local user columns
		'first_name',
		'last_name',
		'mobile_country_code',
		'mobile_number',
		'birthday',
		'profile_image',
		'terms_accepted',
		'join_newsletter',

		// Add roles column
		'user_roles',

		// Add user based service columns
		'active_entries',
		'user_desc',
		'user_level',
		'discount_amount',

        // Add Cashier / Stripe columns
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'email_verified_at',
		'google_id',
		'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
			'birthday' => 'date',
			'join_newsletter' => 'boolean',
        ];
    }

	/**
	 * Use localized user e-mail verification emails
	 */
	public function sendEmailVerificationNotification(): void
	{
		$this->notify((new VerifyEmailNotification)->locale(app()->getLocale()));
	}

	/**
	 * Use localized password resetting emails
	 */
	public function sendPasswordResetNotification($token): void
	{
		$this->notify((new ResetPasswordNotification($token))->locale(app()->getLocale()));
	}

	/**
	 * Check if user has the correct role and permissions
	 */
    public function hasRole($roles)
    {

		// Superadmin has all permissions
		if (!empty(config('racster.superadmin')) and in_array($this->id, config('racster.superadmin'))){
			return true;
		}

		// Get roles from assets
		$role_types = DB::table('racster_assets')
			->where('type', 'userrole')
			->whereNull('deleted_at')
			->pluck('title', 'id')
			->toArray();

		// Get user active roles
		$user_roles = explode('|', $this->user_roles);

		// Block user without roles or if all roles are not registered
		if (empty($this->user_roles) or count(array_intersect(array_keys($role_types), $user_roles)) != count($user_roles)){
			return false;
		}

		// Check if user has correct permissions
		foreach ((!is_array($roles) ? [$roles] : $roles) as $role){
			if (array_key_exists($role, config('racster.allowed')) and count(array_intersect(config('racster.allowed.'.$role), $user_roles)) > 0){
				return true;
			}
		}

		return false;

    }

	/**
	 * Get user level from assets
	 */
	public function level()
	{
		return $this->belongsTo(\App\Models\RacsterAssets::class, 'user_level');
	}

}
