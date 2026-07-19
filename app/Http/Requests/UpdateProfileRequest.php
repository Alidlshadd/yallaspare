<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\IraqiMobileNumber;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20', new IraqiMobileNumber(), User::uniquePhoneRule($this->user()->id, $this->user()->role)],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'remove_profile_photo' => ['sometimes', 'boolean'],
            'dob_day' => ['nullable', 'integer', 'between:1,31', 'required_with:dob_month,dob_year'],
            'dob_month' => ['nullable', 'integer', 'between:1,12', 'required_with:dob_day,dob_year'],
            'dob_year' => ['nullable', 'integer', 'between:1900,' . now()->year, 'required_with:dob_day,dob_month'],
            'country' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => __('user.first_name'),
            'last_name' => __('user.last_name'),
            'phone' => __('user.phone'),
            'profile_photo' => 'profile photo',
            'dob_day' => 'birth day',
            'dob_month' => 'birth month',
            'dob_year' => 'birth year',
            'country' => __('user.country'),
            'city' => __('user.city'),
            'address_line1' => __('user.address_line'),
            'address_line2' => __('user.building_apartment'),
            'notes' => __('user.notes'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $day = $this->input('dob_day');
            $month = $this->input('dob_month');
            $year = $this->input('dob_year');

            $hasAnyDob = $day !== null || $month !== null || $year !== null;
            $hasAllDob = $day !== null && $month !== null && $year !== null;

            if (! $hasAnyDob || ! $hasAllDob) {
                return;
            }

            $dayInt = (int) $day;
            $monthInt = (int) $month;
            $yearInt = (int) $year;

            if (! checkdate($monthInt, $dayInt, $yearInt)) {
                $validator->errors()->add('dob_day', 'Please enter a valid date of birth.');
                return;
            }

            $dob = Carbon::createFromDate($yearInt, $monthInt, $dayInt);
            if ($dob->isFuture()) {
                $validator->errors()->add('dob_day', 'Date of birth cannot be in the future.');
            }
        });
    }
}
