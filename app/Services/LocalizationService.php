<?php

namespace App\Services;

use App\Models\Label;
use App\Models\Language;
use App\Models\Localization;
use App\Models\LocalizationKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LocalizationService
{
    /**
     * @param  array{key: string, language: string, content: string, status?: string, context?: string|null, created_by_user_id?: int|null, labels?: list<string>}  $data
     */
    public function create(array $data): Localization
    {
        return DB::transaction(function () use ($data): Localization {
            $language = Language::query()->where('code', $data['language'])->firstOrFail();

            $localizationKey = LocalizationKey::query()->firstOrCreate([
                'key' => $data['key'],
            ]);

            if (! empty($data['labels'])) {
                $this->syncLabels($localizationKey, $data['labels']);
            }

            $localization = Localization::query()->where('localization_key_id', $localizationKey->id)
                ->where('language_id', $language->id)
                ->first();

            if ($localization) {
                $localization->content = $data['content'];
                $localization->status = $data['status'] ?? $localization->status;
                $localization->context = array_key_exists('context', $data) ? $data['context'] : $localization->context;
                $localization->version += 1;
                if (isset($data['created_by_user_id'])) {
                    $localization->created_by_user_id = $data['created_by_user_id'];
                }
                $localization->save();
            } else {
                $localization = Localization::query()->create([
                    'localization_key_id' => $localizationKey->id,
                    'language_id' => $language->id,
                    'content' => $data['content'],
                    'status' => $data['status'] ?? 'approved',
                    'context' => $data['context'] ?? null,
                    'version' => 1,
                    'created_by_user_id' => $data['created_by_user_id'] ?? null,
                ]);
            }

            return $localization->load(['localizationKey.labels', 'language', 'createdBy']);
        });
    }

    /**
     * @param  array{content?: string, status?: string, context?: string|null, created_by_user_id?: int|null, labels?: list<string>, language?: string, key?: string}  $data
     */
    public function update(Localization $localization, array $data): Localization
    {
        return DB::transaction(function () use ($localization, $data): Localization {
            $contentChanged = false;

            if (isset($data['content']) && $data['content'] !== $localization->content) {
                $localization->content = $data['content'];
                $contentChanged = true;
            }

            if (isset($data['status'])) {
                $localization->status = $data['status'];
            }

            if (array_key_exists('context', $data)) {
                $localization->context = $data['context'];
            }

            if (isset($data['created_by_user_id'])) {
                $localization->created_by_user_id = $data['created_by_user_id'];
            }

            if (isset($data['language'])) {
                $language = Language::query()->where('code', $data['language'])->firstOrFail();
                $localization->language_id = $language->id;
            }

            if (isset($data['key'])) {
                $localizationKey = LocalizationKey::query()->firstOrCreate(['key' => $data['key']]);
                $localization->localization_key_id = $localizationKey->id;
            }

            if ($contentChanged || isset($data['status']) || isset($data['context'])) {
                $localization->version += 1;
            }

            $localization->save();

            if (array_key_exists('labels', $data)) {
                $this->syncLabels($localization->localizationKey, $data['labels'] ?? []);
            }

            return $localization->fresh(['localizationKey.labels', 'language', 'createdBy']);
        });
    }

    /**
     * @param  array{q?: string, key?: string, content?: string, language?: string, status?: string, labels?: list<string>, per_page?: int}  $filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Localization::query()
            ->with(['localizationKey.labels', 'language', 'createdBy'])
            ->select('localizations.*');

        if (! empty($filters['language'])) {
            $query->whereHas('language', function (Builder $builder) use ($filters): void {
                $builder->where('code', $filters['language']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder
                    ->where('content', 'like', $term)
                    ->orWhereHas('localizationKey', function (Builder $keyQuery) use ($term): void {
                        $keyQuery->where('key', 'like', $term);
                    });
            });
        }

        if (! empty($filters['key'])) {
            $query->whereHas('localizationKey', function (Builder $builder) use ($filters): void {
                $builder->where('key', 'like', '%'.$filters['key'].'%');
            });
        }

        if (! empty($filters['content'])) {
            $query->where('content', 'like', '%'.$filters['content'].'%');
        }

        if (! empty($filters['labels'])) {
            $labels = $filters['labels'];
            $query->whereHas('localizationKey.labels', function (Builder $builder) use ($labels): void {
                $builder->whereIn('name', $labels);
            });
        }

        $perPage = min((int) ($filters['per_page'] ?? 25), 100);

        return $query->latest('localizations.id')->paginate($perPage);
    }

    /**
     * @param  list<string>  $labelNames
     */
    private function syncLabels(LocalizationKey $localizationKey, array $labelNames): void
    {
        $labelIds = collect($labelNames)
            ->filter()
            ->unique()
            ->map(fn (string $name): int => Label::query()->firstOrCreate(['name' => $name])->id)
            ->all();

        $localizationKey->labels()->sync($labelIds);
    }
}
