<?php

namespace App\Http\Resources;

use App\Models\Localization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Localization */
class LocalizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->localizationKey?->key,
            'language' => $this->language?->code,
            'content' => $this->content,
            'status' => $this->status,
            'context' => $this->context,
            'version' => $this->version,
            'created_by_user_id' => $this->created_by_user_id,
            'created_by' => $this->createdBy?->name,
            'labels' => $this->localizationKey?->labels?->pluck('name')->values() ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
