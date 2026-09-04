<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Language\StoreLanguageRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LanguageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LanguageResource::collection(
            Language::query()->orderBy('code')->get()
        );
    }

    public function store(StoreLanguageRequest $request): JsonResponse
    {
        $language = Language::query()->create($request->validated());

        return (new LanguageResource($language))
            ->response()
            ->setStatusCode(201);
    }
}
