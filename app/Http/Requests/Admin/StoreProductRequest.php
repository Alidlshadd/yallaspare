<?php

namespace App\Http\Requests\Admin;

use App\Support\Pricing\ExchangeRate;
use App\Support\ProductImageUpload;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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
     * Why a picture was turned away, in the admin's own language.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tooLarge = __('The image is too large. The maximum size is :limit MB per image.', [
            'limit' => ProductImageUpload::maxMegabytes(),
        ]);
        $unsupported = __('Unsupported image format. Please upload a JPG, PNG or WEBP file.');

        $messages = [];

        foreach (['image' => 'image', 'gallery_images' => 'gallery_images.*'] as $field => $rule) {
            $messages[$rule.'.uploaded'] = $this->uploadFailureMessage($field, $tooLarge);
            $messages[$rule.'.image'] = $unsupported;
            $messages[$rule.'.mimes'] = $unsupported;
            $messages[$rule.'.max'] = $tooLarge;
        }

        return $messages;
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
        $imageRules = [
            'image',
            'mimes:'.implode(',', ProductImageUpload::EXTENSIONS),
            'max:'.ProductImageUpload::maxKilobytes(),
        ];

        return [
            'name_en' => ['required'],
            'name_ar' => ['required'],
            'name_ku' => ['required'],
            'description_en' => ['nullable', 'string'],
            'description_ar' => ['nullable', 'string'],
            'description_ku' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'dealer_price' => ['nullable', 'numeric', 'min:0'],
            'price_currency' => ['nullable', Rule::in(ExchangeRate::CURRENCIES)],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'oem_number' => ['nullable', 'string', 'max:120'],
            'part_number' => ['nullable', 'string', 'max:120'],
            'warranty' => ['nullable', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:100'],
            'product_brand_id' => ['nullable', 'integer', 'exists:product_brands,id'],
            'compatible_models' => ['nullable', 'string'],
            'image' => ['nullable', ...$imageRules],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => $imageRules,
            'is_active' => ['sometimes', 'boolean'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }

    /**
     * A dollar price needs a rate to be turned into dinars, and has to be an
     * amount in dollars and cents — not "1e3", and nothing so large the
     * converted price would not fit its column.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('price_currency') !== ExchangeRate::USD) {
                return;
            }

            if (! ExchangeRate::isConfigured()) {
                $validator->errors()->add('price_currency', __('Set the exchange rate in Settings before pricing a product in USD.'));

                return;
            }

            foreach (['price', 'dealer_price', 'cost_price'] as $field) {
                $value = $this->input($field);

                if ($value === null || $value === '') {
                    continue;
                }

                if (! is_scalar($value) || preg_match('/^\d{1,7}(\.\d{1,2})?$/', trim((string) $value)) !== 1) {
                    $validator->errors()->add($field, __('Enter a USD amount with at most two decimals.'));

                    continue;
                }

                // The dinar price has to fit the column it is stored in.
                if (bccomp(ExchangeRate::toIqd(trim((string) $value)), '99999999', 0) > 0) {
                    $validator->errors()->add($field, __('This USD amount is too large at the current exchange rate.'));
                }
            }
        });
    }

    /**
     * The admin is told what to do next; the log is told what PHP said.
     */
    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();

        foreach (['image', 'gallery_images'] as $field) {
            foreach ($this->uploadsFor($field) as $index => $file) {
                $key = $field === 'image' ? 'image' : $field.'.'.$index;

                if (! isset($failed[$key])) {
                    continue;
                }

                Log::warning('Product image upload rejected', [
                    'field' => $key,
                    'rules' => array_keys($failed[$key]),
                    'php_upload_error' => $file->getError(),
                    'php_upload_error_message' => $file->getError() !== UPLOAD_ERR_OK ? $file->getErrorMessage() : null,
                    'client_name' => $file->getClientOriginalName(),
                    'client_mime' => $file->getClientMimeType(),
                    'size_bytes' => $file->isValid() ? $file->getSize() : null,
                    'limit_kilobytes' => ProductImageUpload::maxKilobytes(),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'user_id' => $this->user()?->getAuthIdentifier(),
                    'route' => $this->route()?->getName(),
                ]);
            }
        }

        parent::failedValidation($validator);
    }

    /**
     * A file PHP itself refused arrives with an error code and no content.
     * Only the "too big for the server" codes are worth telling apart: the
     * rest are faults on our side the admin can do nothing about but retry.
     */
    private function uploadFailureMessage(string $field, string $tooLarge): string
    {
        foreach ($this->uploadsFor($field) as $file) {
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return $tooLarge;
            }
        }

        return __('The image could not be uploaded. Please try again.');
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function uploadsFor(string $field): array
    {
        return array_filter(
            Arr::wrap($this->file($field)),
            fn ($file): bool => $file instanceof UploadedFile
        );
    }
}
