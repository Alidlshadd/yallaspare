<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGovernorateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route already gates on settings.manage.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:64', Rule::unique('governorates', 'name_en')],
            'name_ar' => ['required', 'string', 'max:64'],
            'name_ku' => ['required', 'string', 'max:64'],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:60'],
            'shipping_fee' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name_en' => __('Name (English)'),
            'name_ar' => __('Name (Arabic)'),
            'name_ku' => __('Name (Kurdish)'),
            'delivery_days' => __('Delivery time (days)'),
            'shipping_fee' => __('Shipping fee (IQD)'),
        ];
    }
}
