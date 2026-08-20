<?php

namespace Database\Factories;

use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Governorate>
 */
class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'code' => Str::slug($name),
            'name_en' => $name,
            'name_ar' => $name,
            'name_ku' => $name,
            'delivery_days' => fake()->numberBetween(1, 7),
            'shipping_fee' => fake()->numberBetween(0, 20000),
            'sort_order' => fake()->numberBetween(1, 19),
        ];
    }
}
