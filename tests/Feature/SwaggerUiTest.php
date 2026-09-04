<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwaggerUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_swagger_ui_page_loads_successfully(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('Swagger UI')
            ->assertSee('Localization Management Service API Documentation');

        $this->get('/swagger')
            ->assertOk();

        $this->get('/api/documentation')
            ->assertOk();
    }

    public function test_openapi_json_spec_endpoint_returns_valid_json(): void
    {
        $response = $this->get('/docs/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('info.title', 'Localization Management Service API');

        $this->assertArrayHasKey('/login', $response->json('paths'));
        $this->assertArrayHasKey('/user', $response->json('paths'));
        $this->assertArrayHasKey('/localizations', $response->json('paths'));
        $this->assertArrayHasKey('/localizations/export', $response->json('paths'));
    }
}
