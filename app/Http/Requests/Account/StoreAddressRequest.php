<?php

namespace App\Http\Requests\Account;

use App\Rules\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:50'],
            'country' => ['required', 'string', 'max:120'],
            'governorate_id' => ['required', 'integer', Rule::exists('governorates', 'id')],
            'city' => ['required', 'string', 'max:120'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', new PhoneNumber],
            'is_default' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'governorate_id' => __('Governorate'),
            'address_line1' => __('Address Line 1'),
            'address_line2' => __('Address Line 2'),
        ];
    }
}
