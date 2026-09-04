<?php

namespace Database\Seeders;

use App\Services\LocalizationService;
use Illuminate\Database\Seeder;

class LocalizationSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(LocalizationService::class);

        $service->create([
            'key' => 'home.welcome',
            'language' => 'en',
            'content' => 'Welcome',
            'status' => 'approved',
            'context' => 'Homepage main greeting',
            'labels' => ['web', 'mobile'],
        ]);

        $service->create([
            'key' => 'home.welcome',
            'language' => 'fr',
            'content' => 'Bienvenue',
            'status' => 'approved',
            'context' => 'Homepage main greeting',
            'labels' => ['web', 'mobile'],
        ]);

        $service->create([
            'key' => 'auth.login',
            'language' => 'en',
            'content' => 'Sign in',
            'status' => 'approved',
            'context' => 'Login button label',
            'labels' => ['web'],
        ]);
    }
}
