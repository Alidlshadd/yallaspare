<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return array_merge($this->coreRules(), [
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'remove_image' => ['sometimes', 'boolean'],
        ]);
    }
}
