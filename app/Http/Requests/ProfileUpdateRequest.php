<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

use Carbon\Carbon;

class ProfileUpdateRequest extends FormRequest
{

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{

		return [
/* Name & E-mail are not changeable
			'name'					=> ['required', 'string', 'max:255'],
			'email'					=> [
				'required',
				'string',
				'lowercase',
				'email',
				'max:255',
				Rule::unique(User::class)->ignore($this->user()->id),
			],
*/
			'first_name'			=> ['required', 'string', 'max:255'],
			'last_name'				=> ['required', 'string', 'max:255'],
			'mobile_country_code'	=> ['required', 'regex:/^\+\d{1,4}$/'],
			'mobile_number'			=> ['required',
				function ($attribute, $value, $fail) {
					$fullNumber = $this->input('mobile_country_code') . $value;
					try {
						$phoneUtil = PhoneNumberUtil::getInstance();
						$phoneNumber = $phoneUtil->parse($fullNumber, null);
						if (! $phoneUtil->isValidNumber($phoneNumber)) {
							$fail(trans('racster.phone-number-is-invalid'));
						}
					} catch (NumberParseException $e) {
						$fail(trans('racster.phone-number-is-invalid'));
					}
				},
			],
			'birthday'				=> ['required', 'date', 'date_format:Y-m-d', 'before:today'],
			'profile_image'			=> ['nullable', 'image', 'max:'.(config('racster.max_avatar_mb') * 1024)],
			'terms_accepted'		=> ['accepted'],
			'join_newsletter'		=> ['nullable', 'boolean'],
			'user_level'			=> [Rule::requiredIf(!$this->user()->user_level && !$this->user()->hasRole('manager')), 'string', 'max:255'],
		];

	}

	/**
	 * Normalize the request variables
	 */
	protected function prepareForValidation(): void
	{

		$birthday = $this->birthday;

		if ($birthday && Carbon::hasFormat($birthday, 'd.m.Y')) {
			$birthday = Carbon::createFromFormat('d.m.Y', $birthday)
				->format('Y-m-d');
		} else {
			$birthday = null;
		}

		$this->merge([
			'join_newsletter'	=> $this->boolean('join_newsletter'),
			'birthday'			=> $birthday,
		]);

	}

	/**
	 * Add custom validation translations
	 */
	public function messages(): array
	{
		return [
			'birthday.before' => __('validation.before_today'),
		];
	}

}
