<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return array_merge(
            $this->productCoreRules(),
            [
                'sku' => [
                    'nullable',
                    'string',
                    'max:64',
                    Rule::unique('products', 'sku')->ignore($productId),
                ],
                'remove_image' => ['sometimes', 'boolean'],
                'remove_gallery_image_ids' => ['nullable', 'array'],
                'remove_gallery_image_ids.*' => ['integer', 'exists:product_images,id'],
                'primary_image_id' => ['nullable', 'integer', 'exists:product_images,id'],
                'gallery_sort_order' => ['nullable', 'array'],
                'gallery_sort_order.*' => ['nullable', 'integer', 'min:0', 'max:10000'],
                'gallery_alt_text' => ['nullable', 'array'],
                'gallery_alt_text.*' => ['nullable', 'string', 'max:255'],
            ]
        );
    }
}
