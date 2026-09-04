<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Localization\ExportLocalizationRequest;
use App\Http\Requests\Localization\SearchLocalizationRequest;
use App\Http\Requests\Localization\StoreLocalizationRequest;
use App\Http\Requests\Localization\UpdateLocalizationRequest;
use App\Http\Resources\LocalizationResource;
use App\Models\Localization;
use App\Services\LocalizationExportService;
use App\Services\LocalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocalizationController extends Controller
{
    public function __construct(
        private readonly LocalizationService $localizationService,
        private readonly LocalizationExportService $exportService,
    ) {}

    public function index(SearchLocalizationRequest $request): AnonymousResourceCollection
    {
        return $this->search($request);
    }

    public function search(SearchLocalizationRequest $request): AnonymousResourceCollection
    {
        $results = $this->localizationService->search($request->validated());

        return LocalizationResource::collection($results);
    }

    public function store(StoreLocalizationRequest $request): JsonResponse
    {
        $payload = array_merge(
            ['created_by_user_id' => $request->user()?->id],
            $request->validated()
        );

        $localization = $this->localizationService->create($payload);
        $this->exportService->invalidate($request->validated('language'));

        return (new LocalizationResource($localization))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Localization $localization): LocalizationResource
    {
        $localization->load(['localizationKey.labels', 'language', 'createdBy']);

        return new LocalizationResource($localization);
    }

    public function update(UpdateLocalizationRequest $request, Localization $localization): LocalizationResource
    {
        $payload = array_merge(
            ['created_by_user_id' => $request->user()?->id],
            $request->validated()
        );

        $updated = $this->localizationService->update($localization, $payload);
        $this->exportService->invalidate($updated->language?->code);

        return new LocalizationResource($updated);
    }

    public function destroy(Localization $localization): JsonResponse
    {
        $localization->loadMissing('language');
        $languageCode = $localization->language?->code;
        $localization->delete();
        $this->exportService->invalidate($languageCode);

        return response()->json(null, 204);
    }

    public function export(ExportLocalizationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $payload = $this->exportService->export(
            $data['language'],
            $data['labels'] ?? null,
            filter_var($data['include_metadata'] ?? true, FILTER_VALIDATE_BOOLEAN),
            $data['status'] ?? null,
        );

        return response()
            ->json($payload)
            ->header('Cache-Control', 'public, max-age=30, stale-while-revalidate=60')
            ->header('CDN-Cache-Control', 'max-age=30');
    }
}
