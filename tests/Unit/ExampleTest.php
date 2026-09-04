<?php

namespace Tests\Unit;

use App\Services\LocalizationExportService;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_export_service_is_resolvable(): void
    {
        $this->assertInstanceOf(
            LocalizationExportService::class,
            $this->app->make(LocalizationExportService::class)
        );
    }
}
