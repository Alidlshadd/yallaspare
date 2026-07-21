<?php

namespace Database\Factories;

use App\Models\Popup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Popup>
 */
class PopupFactory extends Factory
{
    protected $model = Popup::class;

    public function definition(): array
    {
        return [
            'title_en' => fake()->unique()->sentence(3),
            'description_en' => fake()->sentence(),
            'button_label_en' => 'Shop now',
            'button_url' => '/shop',
            'is_active' => true,
            'pages' => ['all'],
            'frequency' => 'once_per_days',
            'frequency_days' => 7,
            'delay_seconds' => 0,
        ];
    }
}
