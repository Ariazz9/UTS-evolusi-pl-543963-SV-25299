<?php

namespace Database\Factories;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    protected $model = Material::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoriesWithUnits = [
            'Semen & Mortar' => ['Sak', 'Zak'],
            'Besi & Baja' => ['Batang', 'Lonjor'],
            'Cat & Pelapis' => ['Kaleng', 'Pail', 'Galon'],
            'Pipa & Plumbing' => ['Batang', 'Pcs'],
            'Keramik & Granit' => ['Dus', 'Box'],
            'Kayu & Triplek' => ['Lembar', 'Batang'],
        ];

        $category = fake()->randomElement(array_keys($categoriesWithUnits));
        $unit = fake()->randomElement($categoriesWithUnits[$category]);

        return [
            'name' => fake()->words(3, true),
            'category' => $category,
            'unit' => $unit,
            'price' => fake()->randomFloat(2, 15000, 500000),
            'stock' => fake()->numberBetween(5, 200),
            'description' => fake()->sentence(),
        ];
    }
}

