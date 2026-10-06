<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Phone numbers for the customer directory, which is not only Iraqi.
 *
 * A number is typed the way people write it at home — 0770…, 0912…, 0532… —
 * next to the country it belongs to, and kept in E.164 so that one number is
 * one string however it was entered. The country only decides how a local
 * spelling is read: a number that already starts with + or 00 says for itself
 * where it is from, and nothing is ever prefixed onto it.
 */
final class InternationalPhone
{
    public const DEFAULT_COUNTRY = 'IQ';

    /**
     * The countries offered in the pickers: Iraq, its neighbours, the Gulf,
     * and where the diaspora and the suppliers are. Any other country still
     * works by typing the number in full with its + code.
     *
     * @var array<string, string> ISO 3166-1 alpha-2 => calling code
     */
    private const DIAL_CODES = [
        'IQ' => '964', 'IR' => '98', 'TR' => '90', 'SY' => '963', 'JO' => '962',
        'KW' => '965', 'SA' => '966', 'AE' => '971', 'QA' => '974', 'BH' => '973',
        'OM' => '968', 'LB' => '961', 'EG' => '20', 'AZ' => '994', 'AM' => '374',
        'GE' => '995', 'DE' => '49', 'GB' => '44', 'SE' => '46', 'NL' => '31',
        'FR' => '33', 'NO' => '47', 'DK' => '45', 'AT' => '43', 'US' => '1',
        'CA' => '1', 'AU' => '61', 'CN' => '86', 'IN' => '91', 'RU' => '7',
    ];

    /**
     * @return array<string, array{dial: string, name: string}>
     */
    public static function countries(): array
    {
        $names = self::names();
        $countries = [];

        foreach (self::DIAL_CODES as $iso => $dial) {
            $countries[$iso] = ['dial' => $dial, 'name' => $names[$iso]];
        }

        return $countries;
    }

    public static function isKnownCountry(mixed $iso): bool
    {
        return is_string($iso) && isset(self::DIAL_CODES[strtoupper($iso)]);
    }

    /** The name to print for a stored country code; the code itself if it is not one we list. */
    public static function countryName(?string $iso): string
    {
        $iso = strtoupper((string) $iso);

        return self::names()[$iso] ?? $iso;
    }

    /**
     * The number in E.164, or null if it is not a valid number for its country.
     */
    public static function toE164(mixed $value, ?string $country = null): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $number = self::asciiDigits(trim((string) $value));

        if ($number === '') {
            return null;
        }

        $country = self::isKnownCountry($country) ? strtoupper((string) $country) : self::DEFAULT_COUNTRY;
        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($number, $country);
        } catch (NumberParseException) {
            return null;
        }

        return $util->isValidNumber($parsed) ? $util->format($parsed, PhoneNumberFormat::E164) : null;
    }

    /**
     * A stored number taken apart again for the form: which country to select
     * and what to show in the box beside it.
     *
     * @return array{country: string, number: string}
     */
    public static function split(?string $e164): array
    {
        $e164 = trim((string) $e164);

        if ($e164 === '') {
            return ['country' => self::DEFAULT_COUNTRY, 'number' => ''];
        }

        $util = PhoneNumberUtil::getInstance();

        try {
            $parsed = $util->parse($e164, self::DEFAULT_COUNTRY);
            $region = (string) $util->getRegionCodeForNumber($parsed);
        } catch (NumberParseException) {
            $region = '';
        }

        // A country outside the list has no entry to select, so its number is
        // shown whole; with the + in front it is read the same way on saving.
        if (! isset($parsed) || ! isset(self::DIAL_CODES[$region])) {
            return ['country' => self::DEFAULT_COUNTRY, 'number' => $e164];
        }

        return ['country' => $region, 'number' => $util->getNationalSignificantNumber($parsed)];
    }

    /** Arabic-Indic and Persian digits are what a phone keyboard in Iraq or Iran produces. */
    private static function asciiDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function names(): array
    {
        return [
            'IQ' => __('Iraq'), 'IR' => __('Iran'), 'TR' => __('Türkiye'), 'SY' => __('Syria'), 'JO' => __('Jordan'),
            'KW' => __('Kuwait'), 'SA' => __('Saudi Arabia'), 'AE' => __('United Arab Emirates'), 'QA' => __('Qatar'), 'BH' => __('Bahrain'),
            'OM' => __('Oman'), 'LB' => __('Lebanon'), 'EG' => __('Egypt'), 'AZ' => __('Azerbaijan'), 'AM' => __('Armenia'),
            'GE' => __('Georgia'), 'DE' => __('Germany'), 'GB' => __('United Kingdom'), 'SE' => __('Sweden'), 'NL' => __('Netherlands'),
            'FR' => __('France'), 'NO' => __('Norway'), 'DK' => __('Denmark'), 'AT' => __('Austria'), 'US' => __('United States'),
            'CA' => __('Canada'), 'AU' => __('Australia'), 'CN' => __('China'), 'IN' => __('India'), 'RU' => __('Russia'),
        ];
    }
}
