<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'fr', 'name' => 'French'],
            ['code' => 'es', 'name' => 'Spanish'],
        ] as $language) {
            Language::query()->updateOrCreate(
                ['code' => $language['code']],
                ['name' => $language['name'], 'is_active' => true]
            );
        }
    }
}
