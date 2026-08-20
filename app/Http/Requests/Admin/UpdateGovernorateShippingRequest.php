<?php

namespace App\Http\Requests\Admin;

use App\Models\Governorate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGovernorateShippingRequest extends FormRequest
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
            // The form posts every governorate on every save, so a payload
            // that is short or long means it was not the form that sent it.
            'rows' => ['required', 'array', 'size:'.Governorate::query()->count()],
            'rows.*.id' => ['required', 'integer', 'distinct', 'exists:governorates,id'],
            'rows.*.delivery_days' => ['required', 'integer', 'min:1', 'max:60'],
            'rows.*.shipping_fee' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rows.*.delivery_days' => __('Delivery time (days)'),
            'rows.*.shipping_fee' => __('Shipping fee (IQD)'),
        ];
    }
}
