<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocalizationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_endpoint_responds_quickly_for_moderate_dataset(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        Language::query()->create(['code' => 'en', 'name' => 'English', 'is_active' => true]);
        Label::query()->create(['name' => 'web']);

        Artisan::call('localizations:seed-bulk', ['--count' => 500, '--chunk' => 100]);

        $this->assertGreaterThanOrEqual(500, DB::table('localizations')->count());

        $start = hrtime(true);

        $response = $this->withToken($token)->getJson('/api/localizations/export?language=en');

        $elapsedMs = (hrtime(true) - $start) / 1_000_000;

        $response->assertOk();
        $this->assertArrayHasKey('localizations', $response->json());
        $this->assertLessThan(500, $elapsedMs, "Export took {$elapsedMs}ms");
    }
}
