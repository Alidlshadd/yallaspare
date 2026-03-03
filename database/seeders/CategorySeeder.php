<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
       


        Category::create([
            'name_en' => 'Engine Parts',
            'name_ar' => 'قطع المحرك',
            'name_ku' => 'Parçeyên Motor',
            'slug'    => 'engine-parts'
        ]);

        Category::create([
            'name_en' => 'Brakes',
            'name_ar' => 'الفرامل',
            'name_ku' => 'Fren',
            'slug'    => 'brakes'
        ]);

        Category::create([
            'name_en' => 'Suspension',
            'name_ar' => 'نظام التعليق',
            'name_ku' => 'Suspension',
            'slug'    => 'suspension'
        ]);

        $sampleCategories = [
            [
                'name_en' => 'Sample - Electrical & Lighting',
                'name_ar' => 'Sample - Electrical & Lighting',
                'name_ku' => 'Sample - Electrical & Lighting',
                'slug' => 'sample-electrical-lighting',
                'description' => 'Sample data for UI testing.',
            ],
            [
                'name_en' => 'Sample - Filters & Fluids',
                'name_ar' => 'Sample - Filters & Fluids',
                'name_ku' => 'Sample - Filters & Fluids',
                'slug' => 'sample-filters-fluids',
                'description' => 'Sample data for UI testing.',
            ],
            [
                'name_en' => 'Sample - Cooling System',
                'name_ar' => 'Sample - Cooling System',
                'name_ku' => 'Sample - Cooling System',
                'slug' => 'sample-cooling-system',
                'description' => 'Sample data for UI testing.',
            ],
            [
                'name_en' => 'Sample - Steering & Drivetrain',
                'name_ar' => 'Sample - Steering & Drivetrain',
                'name_ku' => 'Sample - Steering & Drivetrain',
                'slug' => 'sample-steering-drivetrain',
                'description' => 'Sample data for UI testing.',
            ],
            [
                'name_en' => 'Sample - Body & Exterior',
                'name_ar' => 'Sample - Body & Exterior',
                'name_ku' => 'Sample - Body & Exterior',
                'slug' => 'sample-body-exterior',
                'description' => 'Sample data for UI testing.',
            ],
        ];

        foreach ($sampleCategories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
