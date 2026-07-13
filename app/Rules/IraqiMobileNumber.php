<?php

namespace App\Rules;

use App\Support\IraqiPhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IraqiMobileNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hasFormatError = false;

        (new PhoneNumber())->validate($attribute, $value, function () use (&$hasFormatError): void {
            $hasFormatError = true;
        });

        if ($hasFormatError || IraqiPhoneNumber::toE164($value) === null) {
            $fail(__('validation.iraqi_phone'));
        }
    }
}
