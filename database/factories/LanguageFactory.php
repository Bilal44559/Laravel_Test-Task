<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->languageCode();

        return [
            'code' => $code,
            'name' => strtoupper($code).' Language',
            'is_active' => true,
        ];
    }
}
