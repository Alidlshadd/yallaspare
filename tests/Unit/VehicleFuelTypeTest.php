<?php

namespace Tests\Unit;

use App\Support\VehicleFuelType;
use Tests\TestCase;

class VehicleFuelTypeTest extends TestCase
{
    public function test_only_the_four_canonical_values_are_valid(): void
    {
        $this->assertSame(['petrol', 'diesel', 'hybrid', 'electric'], VehicleFuelType::all());

        foreach (VehicleFuelType::all() as $fuelType) {
            $this->assertTrue(VehicleFuelType::isValid($fuelType));
        }

        $this->assertFalse(VehicleFuelType::isValid('hydrogen'));
        $this->assertFalse(VehicleFuelType::isValid('Petrol'));
        $this->assertFalse(VehicleFuelType::isValid(null));
    }

    public function test_labels_are_translated_but_the_stored_value_is_not(): void
    {
        $this->assertSame('Petrol', VehicleFuelType::label('petrol', 'en'));
        $this->assertSame('بنزين', VehicleFuelType::label('petrol', 'ar'));
        $this->assertSame('بەنزین', VehicleFuelType::label('petrol', 'ku'));

        $this->assertSame('Electric', VehicleFuelType::label('electric', 'en'));
        $this->assertSame('كهربائي', VehicleFuelType::label('electric', 'ar'));
        $this->assertSame('کارەبایی', VehicleFuelType::label('electric', 'ku'));
    }

    public function test_every_fuel_type_has_a_label_in_every_locale(): void
    {
        foreach (['en', 'ar', 'ku'] as $locale) {
            foreach (VehicleFuelType::all() as $fuelType) {
                $this->assertNotSame('', VehicleFuelType::label($fuelType, $locale));
            }
        }
    }

    public function test_an_invalid_fuel_type_has_no_label(): void
    {
        $this->assertSame('', VehicleFuelType::label('hydrogen'));
        $this->assertSame('', VehicleFuelType::label(null));
    }

    public function test_display_name_joins_the_parts(): void
    {
        // A whole number of litres keeps its decimal. Trimming it wrote a 2.0
        // as a bare "2", and a customer reading "2 Turbo Petrol" on a product
        // page could not tell which engine was meant.
        $this->assertSame('2.0 Turbo Petrol', VehicleFuelType::displayName('petrol', 2.0, 'turbo', 'en'));
        $this->assertSame('3.0 Petrol', VehicleFuelType::displayName('petrol', 3, null, 'en'));
        $this->assertSame('1.5 Petrol', VehicleFuelType::displayName('petrol', 1.5, null, 'en'));
        $this->assertSame('2.2 Diesel', VehicleFuelType::displayName('diesel', '2.2', null, 'en'));
        $this->assertSame('1.6 تيربو هجين', VehicleFuelType::displayName('hybrid', 1.6, 'turbo', 'ar'));
    }

    public function test_electric_display_name_never_invents_a_displacement(): void
    {
        $this->assertFalse(VehicleFuelType::hasDisplacement('electric'));
        $this->assertSame('Electric', VehicleFuelType::displayName('electric', 2.0, 'turbo', 'en'));
        $this->assertSame('کارەبایی', VehicleFuelType::displayName('electric', null, null, 'ku'));
    }

    public function test_free_text_is_parsed_into_structured_parts(): void
    {
        $this->assertSame(
            ['fuel_type' => 'petrol', 'engine_size' => 2.0, 'aspiration' => 'turbo'],
            VehicleFuelType::parse('2.0 Turbo Petrol'),
        );

        $this->assertSame(
            ['fuel_type' => 'diesel', 'engine_size' => 2.2, 'aspiration' => null],
            VehicleFuelType::parse('2.2 Diesel'),
        );

        $this->assertSame(
            ['fuel_type' => 'hybrid', 'engine_size' => 1.6, 'aspiration' => null],
            VehicleFuelType::parse('1.6L Hybrid'),
        );
    }

    public function test_parsing_an_electric_engine_yields_no_displacement(): void
    {
        $this->assertSame(
            ['fuel_type' => 'electric', 'engine_size' => null, 'aspiration' => null],
            VehicleFuelType::parse('Electric'),
        );
    }

    public function test_unrecognisable_text_parses_to_nulls_rather_than_guessing(): void
    {
        $this->assertSame(
            ['fuel_type' => null, 'engine_size' => null, 'aspiration' => null],
            VehicleFuelType::parse('Standard'),
        );
        $this->assertSame(
            ['fuel_type' => null, 'engine_size' => null, 'aspiration' => null],
            VehicleFuelType::parse(''),
        );
    }
}
