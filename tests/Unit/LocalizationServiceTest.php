<?php

namespace Tests\Unit;

use App\Models\Label;
use App\Models\Language;
use App\Models\Localization;
use App\Models\LocalizationKey;
use App\Models\User;
use App\Services\LocalizationExportService;
use App\Services\LocalizationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LocalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private LocalizationService $service;

    private Language $en;

    private Language $fr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LocalizationService::class);
        $this->en = Language::query()->create(['code' => 'en', 'name' => 'English', 'is_active' => true]);
        $this->fr = Language::query()->create(['code' => 'fr', 'name' => 'French', 'is_active' => true]);
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function test_create_persists_localization_with_labels_and_metadata(): void
    {
        $user = User::factory()->create();

        $localization = $this->service->create([
            'key' => 'dashboard.title',
            'language' => 'en',
            'content' => 'Dashboard',
            'status' => 'draft',
            'context' => 'Dashboard main heading',
            'created_by_user_id' => $user->id,
            'labels' => ['web'],
        ]);

        $this->assertSame('Dashboard', $localization->content);
        $this->assertSame('draft', $localization->status);
        $this->assertSame('Dashboard main heading', $localization->context);
        $this->assertSame(1, $localization->version);
        $this->assertSame($user->id, $localization->created_by_user_id);
        $this->assertSame('dashboard.title', $localization->localizationKey->key);
        $this->assertTrue($localization->localizationKey->labels->contains('name', 'web'));
        $this->assertDatabaseHas('labels', ['name' => 'web']);
        $this->assertDatabaseHas('localizations', [
            'content' => 'Dashboard',
            'status' => 'draft',
            'context' => 'Dashboard main heading',
            'version' => 1,
            'created_by_user_id' => $user->id,
        ]);
    }

    public function test_create_without_labels_persists_localization(): void
    {
        $localization = $this->service->create([
            'key' => 'nav.home',
            'language' => 'en',
            'content' => 'Home',
        ]);

        $this->assertSame('Home', $localization->content);
        $this->assertSame('approved', $localization->status);
        $this->assertSame(1, $localization->version);
        $this->assertEmpty($localization->localizationKey->labels);
    }

    public function test_create_creates_localization_key_once_for_multiple_languages(): void
    {
        $this->service->create(['key' => 'app.title', 'language' => 'en', 'content' => 'App']);
        $this->service->create(['key' => 'app.title', 'language' => 'fr', 'content' => 'App FR']);

        $this->assertSame(1, LocalizationKey::query()->where('key', 'app.title')->count());
        $this->assertSame(2, Localization::query()->count());
    }

    public function test_create_upserts_existing_localization_for_same_key_and_language(): void
    {
        $this->service->create(['key' => 'home.welcome', 'language' => 'en', 'content' => 'Hello']);
        $second = $this->service->create(['key' => 'home.welcome', 'language' => 'en', 'content' => 'Hi']);

        $this->assertSame('Hi', $second->content);
        $this->assertSame(2, $second->version);
        $this->assertSame(1, Localization::query()->count());
    }

    public function test_create_attaches_multiple_labels(): void
    {
        $localization = $this->service->create([
            'key' => 'btn.submit',
            'language' => 'en',
            'content' => 'Submit',
            'labels' => ['web', 'mobile', 'desktop'],
        ]);

        $labelNames = $localization->localizationKey->labels->pluck('name')->sort()->values()->all();
        $this->assertSame(['desktop', 'mobile', 'web'], $labelNames);
    }

    public function test_create_throws_when_language_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->create([
            'key' => 'some.key',
            'language' => 'xx',
            'content' => 'Value',
        ]);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_changes_content_and_increments_version(): void
    {
        $localization = $this->service->create(['key' => 'msg.hello', 'language' => 'en', 'content' => 'Hello']);
        $this->assertSame(1, $localization->version);

        $updated = $this->service->update($localization, ['content' => 'Hi there']);

        $this->assertSame('Hi there', $updated->content);
        $this->assertSame(2, $updated->version);
        $this->assertDatabaseHas('localizations', ['content' => 'Hi there', 'version' => 2]);
    }

    public function test_update_replaces_labels(): void
    {
        $localization = $this->service->create([
            'key' => 'btn.ok',
            'language' => 'en',
            'content' => 'OK',
            'labels' => ['web'],
        ]);

        $updated = $this->service->update($localization, ['labels' => ['mobile']]);

        $labelNames = $updated->localizationKey->fresh('labels')->labels->pluck('name')->all();
        $this->assertSame(['mobile'], $labelNames);
        $this->assertDatabaseHas('labels', ['name' => 'mobile']);
    }

    public function test_update_clears_labels_when_empty_array_given(): void
    {
        $localization = $this->service->create([
            'key' => 'btn.cancel',
            'language' => 'en',
            'content' => 'Cancel',
            'labels' => ['web'],
        ]);

        $updated = $this->service->update($localization, ['labels' => []]);

        $this->assertEmpty($updated->localizationKey->fresh('labels')->labels);
    }

    public function test_update_changes_language(): void
    {
        $localization = $this->service->create(['key' => 'page.title', 'language' => 'en', 'content' => 'Title']);
        $updated = $this->service->update($localization, ['language' => 'fr']);

        $this->assertSame('fr', $updated->language->code);
    }

    // -------------------------------------------------------------------------
    // search()
    // -------------------------------------------------------------------------

    public function test_search_returns_all_when_no_filters(): void
    {
        $this->service->create(['key' => 'a.key', 'language' => 'en', 'content' => 'Alpha']);
        $this->service->create(['key' => 'b.key', 'language' => 'fr', 'content' => 'Beta']);

        $results = $this->service->search([]);

        $this->assertSame(2, $results->total());
    }

    public function test_search_filters_by_language(): void
    {
        $this->service->create(['key' => 'a', 'language' => 'en', 'content' => 'EN']);
        $this->service->create(['key' => 'b', 'language' => 'fr', 'content' => 'FR']);

        $results = $this->service->search(['language' => 'en']);

        $this->assertSame(1, $results->total());
        $this->assertSame('EN', $results->first()->content);
    }

    public function test_search_filters_by_status(): void
    {
        $this->service->create(['key' => 'a', 'language' => 'en', 'content' => 'Draft', 'status' => 'draft']);
        $this->service->create(['key' => 'b', 'language' => 'en', 'content' => 'Approved', 'status' => 'approved']);

        $results = $this->service->search(['status' => 'draft']);

        $this->assertSame(1, $results->total());
        $this->assertSame('Draft', $results->first()->content);
    }

    public function test_search_filters_by_key_partial_match(): void
    {
        $this->service->create(['key' => 'auth.login', 'language' => 'en', 'content' => 'Login']);
        $this->service->create(['key' => 'auth.logout', 'language' => 'en', 'content' => 'Logout']);
        $this->service->create(['key' => 'dashboard', 'language' => 'en', 'content' => 'Dashboard']);

        $results = $this->service->search(['key' => 'auth']);

        $this->assertSame(2, $results->total());
    }

    public function test_search_filters_by_content_partial_match(): void
    {
        $this->service->create(['key' => 'a', 'language' => 'en', 'content' => 'Sign in']);
        $this->service->create(['key' => 'b', 'language' => 'en', 'content' => 'Sign out']);
        $this->service->create(['key' => 'c', 'language' => 'en', 'content' => 'Dashboard']);

        $results = $this->service->search(['content' => 'Sign']);

        $this->assertSame(2, $results->total());
    }

    public function test_search_filters_by_labels(): void
    {
        $this->service->create(['key' => 'a', 'language' => 'en', 'content' => 'Web only', 'labels' => ['web']]);
        $this->service->create(['key' => 'b', 'language' => 'en', 'content' => 'Mobile only', 'labels' => ['mobile']]);

        $results = $this->service->search(['labels' => ['web']]);

        $this->assertSame(1, $results->total());
        $this->assertSame('Web only', $results->first()->content);
    }

    public function test_search_q_matches_key_or_content(): void
    {
        $this->service->create(['key' => 'welcome.message', 'language' => 'en', 'content' => 'Hello world']);
        $this->service->create(['key' => 'error.404', 'language' => 'en', 'content' => 'Page not found']);

        // matches content
        $byContent = $this->service->search(['q' => 'Hello']);
        $this->assertSame(1, $byContent->total());

        // matches key
        $byKey = $this->service->search(['q' => 'welcome']);
        $this->assertSame(1, $byKey->total());
    }

    public function test_search_respects_per_page(): void
    {
        foreach (range(1, 10) as $i) {
            $this->service->create(['key' => "key.{$i}", 'language' => 'en', 'content' => "Value {$i}"]);
        }

        $results = $this->service->search(['per_page' => 3]);

        $this->assertSame(3, $results->perPage());
        $this->assertSame(10, $results->total());
    }

    // -------------------------------------------------------------------------
    // ExportService
    // -------------------------------------------------------------------------

    public function test_export_service_returns_map_with_metadata_and_flat_option(): void
    {
        $key = LocalizationKey::query()->create(['key' => 'app.name']);
        $key->localizations()->create(['language_id' => $this->en->id, 'content' => 'TMS', 'status' => 'approved', 'version' => 1]);
        $label = Label::query()->create(['name' => 'web']);
        $key->labels()->attach($label);

        $export = app(LocalizationExportService::class)->export('en', ['web']);

        $this->assertSame('en', $export['language']);
        $this->assertSame('TMS', $export['localizations']['app.name']['content']);
        $this->assertSame('approved', $export['localizations']['app.name']['status']);

        $flatExport = app(LocalizationExportService::class)->export('en', ['web'], false);
        $this->assertSame(['app.name' => 'TMS'], $flatExport['localizations']);
    }

    public function test_export_excludes_inactive_language(): void
    {
        $inactive = Language::query()->create(['code' => 'de', 'name' => 'German', 'is_active' => false]);
        $key = LocalizationKey::query()->create(['key' => 'greet']);
        $key->localizations()->create(['language_id' => $inactive->id, 'content' => 'Hallo']);

        $export = app(LocalizationExportService::class)->export('de');

        $this->assertEmpty($export['localizations']);
    }

    public function test_export_returns_all_keys_for_language_without_label_filter(): void
    {
        $k1 = LocalizationKey::query()->create(['key' => 'nav.home']);
        $k1->localizations()->create(['language_id' => $this->en->id, 'content' => 'Home']);

        $k2 = LocalizationKey::query()->create(['key' => 'nav.about']);
        $k2->localizations()->create(['language_id' => $this->en->id, 'content' => 'About']);

        $export = app(LocalizationExportService::class)->export('en');

        $this->assertArrayHasKey('nav.home', $export['localizations']);
        $this->assertArrayHasKey('nav.about', $export['localizations']);
    }

    public function test_export_is_invalidated_after_write(): void
    {
        Cache::flush();

        $key = LocalizationKey::query()->create(['key' => 'msg']);
        $key->localizations()->create(['language_id' => $this->en->id, 'content' => 'Old']);

        $exportService = app(LocalizationExportService::class);

        $first = $exportService->export('en');
        $this->assertSame('Old', $first['localizations']['msg']['content']);

        // Simulate write invalidation
        $exportService->invalidate('en');
        $key->localizations()->where('language_id', $this->en->id)->update(['content' => 'New']);

        $second = $exportService->export('en');
        $this->assertSame('New', $second['localizations']['msg']['content']);
    }
}
