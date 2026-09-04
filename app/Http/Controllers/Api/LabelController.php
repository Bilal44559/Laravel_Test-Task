<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Label\StoreLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabelController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LabelResource::collection(
            Label::query()->orderBy('name')->get()
        );
    }

    public function store(StoreLabelRequest $request): JsonResponse
    {
        $label = Label::query()->create($request->validated());

        return (new LabelResource($label))
            ->response()
            ->setStatusCode(201);
    }
}
