<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by the route middleware
        // (can:products.manage + admin + admin.2fa).
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            $this->productCoreRules(),
            [
                'sku' => ['nullable', 'string', 'max:64', Rule::unique('products', 'sku')],
            ]
        );
    }

    /**
     * Shared product rule set. Update extends and overrides 'sku' so the
     * unique check ignores the current row, plus adds remove_image and
     * gallery management fields.
     *
     * @return array<string, array<int, mixed>|string>
     */
    protected function productCoreRules(): array
    {
        return [
            'name_en' => ['required'],
            'name_ar' => ['required'],
            'name_ku' => ['required'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_ku' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'dealer_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'oem_number' => ['nullable', 'string', 'max:120'],
            'part_number' => ['nullable', 'string', 'max:120'],
            'warranty' => ['nullable', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:100'],
            'compatible_models' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'max:4096'],
            'is_active' => ['sometimes', 'boolean'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }
}
