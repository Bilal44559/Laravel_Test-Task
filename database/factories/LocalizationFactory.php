<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Localization;
use App\Models\LocalizationKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Localization>
 */
class LocalizationFactory extends Factory
{
    protected $model = Localization::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'localization_key_id' => LocalizationKey::factory(),
            'language_id' => Language::factory(),
            'content' => fake()->sentence(),
            'status' => 'approved',
            'context' => fake()->optional()->sentence(),
            'version' => 1,
            'created_by_user_id' => User::factory(),
        ];
    }
}
