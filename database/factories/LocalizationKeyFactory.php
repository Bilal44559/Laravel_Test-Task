<?php

namespace Database\Factories;

use App\Models\LocalizationKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocalizationKey>
 */
class LocalizationKeyFactory extends Factory
{
    protected $model = LocalizationKey::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(),
        ];
    }
}
