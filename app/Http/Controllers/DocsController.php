<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\Yaml\Yaml;

class DocsController extends Controller
{
    /**
     * Return OpenAPI 3 spec as JSON.
     */
    public function json(): JsonResponse
    {
        $yamlPath = base_path('openapi.yaml');

        if (! file_exists($yamlPath)) {
            return response()->json(['error' => 'OpenAPI spec file not found'], 404);
        }

        $parsed = Yaml::parseFile($yamlPath);

        return response()->json($parsed);
    }

    /**
     * Render Swagger UI page.
     */
    public function index(): Response
    {
        $jsonUrl = url('/docs/openapi.json');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Localization Management API - Swagger UI</title>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.11.0/swagger-ui.min.css" />
    <link rel="icon" type="image/png" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.11.0/favicon-32x32.png" sizes="32x32" />
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
        .topbar { background-color: #1b1b1b; padding: 10px 0; text-align: center; }
        .topbar-wrapper { display: flex; align-items: center; justify-content: space-between; max-width: 1460px; margin: 0 auto; padding: 0 20px; }
        .topbar-wrapper a { color: #fff; text-decoration: none; font-family: sans-serif; font-weight: bold; font-size: 18px; display: flex; align-items: center; gap: 8px; }
        .topbar-wrapper img { height: 32px; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-wrapper">
            <a href="#">
                <img src="https://raw.githubusercontent.com/swagger-api/swagger-ui/master/dist/favicon-32x32.png" alt="Swagger">
                <span>Localization Management Service API Documentation</span>
            </a>
        </div>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.11.0/swagger-ui-bundle.min.js" charset="UTF-8"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.11.0/swagger-ui-standalone-preset.min.js" charset="UTF-8"></script>
    <script>
    window.onload = function() {
        window.ui = SwaggerUIBundle({
            url: "{$jsonUrl}",
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout",
            persistAuthorization: true
        });
    };
    </script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
