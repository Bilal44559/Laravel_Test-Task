<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;

        Language::query()->create(['code' => 'en', 'name' => 'English', 'is_active' => true]);
        Language::query()->create(['code' => 'fr', 'name' => 'French', 'is_active' => true]);
        Label::query()->create(['name' => 'web']);
        Label::query()->create(['name' => 'mobile']);
    }

    public function test_can_create_localization_with_metadata(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'home.welcome',
            'language' => 'en',
            'content' => 'Welcome',
            'status' => 'draft',
            'context' => 'Home hero section heading',
            'labels' => ['web', 'mobile'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.key', 'home.welcome')
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.content', 'Welcome')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.context', 'Home hero section heading')
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.created_by_user_id', $this->user->id);

        $this->assertEqualsCanonicalizing(['web', 'mobile'], $response->json('data.labels'));

        $this->assertDatabaseHas('localization_keys', ['key' => 'home.welcome']);
        $this->assertDatabaseHas('localizations', [
            'content' => 'Welcome',
            'status' => 'draft',
            'context' => 'Home hero section heading',
            'version' => 1,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    public function test_can_update_localization_and_increments_version(): void
    {
        $create = $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'home.welcome',
            'language' => 'en',
            'content' => 'Welcome',
            'labels' => ['web'],
        ])->json('data');

        $this->assertSame(1, $create['version']);

        $response = $this->withToken($this->token)->putJson('/api/localizations/'.$create['id'], [
            'content' => 'Welcome back',
            'status' => 'approved',
            'labels' => ['mobile'],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.content', 'Welcome back')
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.version', 2);

        $this->assertEqualsCanonicalizing(['mobile'], $response->json('data.labels'));
    }

    public function test_can_view_localization(): void
    {
        $create = $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'home.welcome',
            'language' => 'en',
            'content' => 'Welcome',
        ])->json('data');

        $this->withToken($this->token)
            ->getJson('/api/localizations/'.$create['id'])
            ->assertOk()
            ->assertJsonPath('data.key', 'home.welcome');
    }

    public function test_can_search_by_key_content_status_and_labels(): void
    {
        $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'auth.login',
            'language' => 'en',
            'content' => 'Sign in',
            'status' => 'draft',
            'labels' => ['web'],
        ]);

        $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'auth.logout',
            'language' => 'en',
            'content' => 'Sign out',
            'status' => 'approved',
            'labels' => ['mobile'],
        ]);

        $this->withToken($this->token)
            ->getJson('/api/localizations?status=draft')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'auth.login');

        $this->withToken($this->token)
            ->getJson('/api/localizations?key=login')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($this->token)
            ->getJson('/api/localizations?content=Sign%20out')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($this->token)
            ->getJson('/api/localizations?labels[]=web')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_dedicated_search_endpoint_supports_q_filter(): void
    {
        $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'auth.login',
            'language' => 'en',
            'content' => 'Sign in',
            'labels' => ['web'],
        ]);

        $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'dashboard.title',
            'language' => 'en',
            'content' => 'Overview',
            'labels' => ['mobile'],
        ]);

        $this->withToken($this->token)
            ->getJson('/api/localizations/search?q=login&language=en')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'auth.login');

        $this->withToken($this->token)
            ->getJson('/api/localizations/search?q=Overview')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Overview');
    }

    public function test_export_returns_updated_key_value_map(): void
    {
        $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'home.welcome',
            'language' => 'en',
            'content' => 'Welcome',
            'labels' => ['web'],
        ]);

        $export = $this->withToken($this->token)
            ->getJson('/api/localizations/export?language=en')
            ->assertOk();

        $this->assertSame('en', $export->json('language'));
        $this->assertSame('Welcome', $export->json('localizations')['home.welcome']['content']);
        $this->assertSame('approved', $export->json('localizations')['home.welcome']['status']);
        $export->assertHeader('Cache-Control');

        $flatExport = $this->withToken($this->token)
            ->getJson('/api/localizations/export?language=en&include_metadata=0')
            ->assertOk();

        $this->assertSame('Welcome', $flatExport->json('localizations')['home.welcome']);

        $this->withToken($this->token)->postJson('/api/localizations', [
            'key' => 'home.welcome',
            'language' => 'en',
            'content' => 'Welcome updated',
            'labels' => ['web'],
        ]);

        $updated = $this->withToken($this->token)
            ->getJson('/api/localizations/export?language=en')
            ->assertOk();

        $this->assertSame('Welcome updated', $updated->json('localizations')['home.welcome']['content']);
    }

    public function test_can_create_language_and_label(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/languages', ['code' => 'de', 'name' => 'German'])
            ->assertCreated()
            ->assertJsonPath('data.code', 'de');

        $this->withToken($this->token)
            ->postJson('/api/labels', ['name' => 'desktop'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'desktop');
    }
}
