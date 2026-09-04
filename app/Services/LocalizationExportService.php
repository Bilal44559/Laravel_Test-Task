<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LocalizationExportService
{
    /**
     * Export localizations with metadata (status, context, version) for frontend apps.
     * Always reflects the latest DB state; short cache is invalidated on writes.
     *
     * @param  list<string>|null  $labels
     * @return array{language: string, localizations: array<string, mixed>}
     */
    public function export(string $languageCode, ?array $labels = null, bool $includeMetadata = true, ?string $status = null): array
    {
        $version = (int) Cache::get($this->versionKey($languageCode), 0);
        $cacheKey = $this->cacheKey($languageCode, $labels, $includeMetadata, $status, $version);

        return Cache::remember($cacheKey, now()->addSeconds(30), function () use ($languageCode, $labels, $includeMetadata, $status): array {
            $query = DB::table('localizations')
                ->join('languages', 'languages.id', '=', 'localizations.language_id')
                ->join('localization_keys', 'localization_keys.id', '=', 'localizations.localization_key_id')
                ->where('languages.code', $languageCode)
                ->where('languages.is_active', true);

            if ($status !== null) {
                $query->where('localizations.status', $status);
            }

            $query->select([
                'localization_keys.key',
                'localizations.content',
                'localizations.status',
                'localizations.context',
                'localizations.version',
            ]);

            if (! empty($labels)) {
                $query->whereExists(function ($sub) use ($labels): void {
                    $sub->select(DB::raw(1))
                        ->from('label_localization_key')
                        ->join('labels', 'labels.id', '=', 'label_localization_key.label_id')
                        ->whereColumn('label_localization_key.localization_key_id', 'localization_keys.id')
                        ->whereIn('labels.name', $labels);
                });
            }

            $rows = $query->orderBy('localization_keys.key')->get();

            $localizations = [];
            foreach ($rows as $row) {
                if ($includeMetadata) {
                    $localizations[$row->key] = [
                        'content' => $row->content,
                        'status' => $row->status,
                        'context' => $row->context,
                        'version' => (int) $row->version,
                    ];
                } else {
                    $localizations[$row->key] = $row->content;
                }
            }

            return [
                'language' => $languageCode,
                'localizations' => $localizations,
            ];
        });
    }

    public function invalidate(?string $languageCode = null): void
    {
        if ($languageCode !== null) {
            Cache::increment($this->versionKey($languageCode));

            return;
        }

        foreach (['en', 'fr', 'es'] as $code) {
            Cache::increment($this->versionKey($code));
        }
    }

    private function versionKey(string $languageCode): string
    {
        return "localizations:export:version:{$languageCode}";
    }

    /**
     * @param  list<string>|null  $labels
     */
    private function cacheKey(string $languageCode, ?array $labels, bool $includeMetadata, ?string $status, int $version): string
    {
        $labelPart = empty($labels) ? 'all' : implode(',', collect($labels)->sort()->values()->all());
        $metaPart = $includeMetadata ? 'meta' : 'flat';
        $statusPart = $status ?? 'all';

        return "localizations:export:{$languageCode}:{$labelPart}:{$metaPart}:{$statusPart}:v{$version}";
    }
}
