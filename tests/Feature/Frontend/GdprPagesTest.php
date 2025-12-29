<?php

namespace Tests\Feature\Frontend;

use App\Models\ConsentTracking;
use App\Models\CookieConsent;
use App\Models\PrivacyPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_policy_show_page_renders(): void
    {
        PrivacyPolicy::create([
            'version' => '1.0',
            'title' => 'Privacy Policy',
            'content' => 'Test policy content.',
            'summary' => 'Short summary.',
            'is_active' => true,
            'is_current' => true,
            'effective_date' => now(),
        ]);

        $this->get(route('gdpr.privacy-policy.show'))
            ->assertOk()
            ->assertSee('Privacy Policy');
    }

    public function test_privacy_policy_versions_page_renders(): void
    {
        PrivacyPolicy::create([
            'version' => '1.0',
            'title' => 'Policy v1',
            'content' => 'Content v1',
            'is_active' => true,
            'is_current' => false,
            'effective_date' => now()->subYear(),
        ]);

        PrivacyPolicy::create([
            'version' => '2.0',
            'title' => 'Policy v2',
            'content' => 'Content v2',
            'is_active' => true,
            'is_current' => true,
            'effective_date' => now(),
        ]);

        $this->get(route('gdpr.privacy-policy.index'))
            ->assertOk()
            ->assertSee('Privacy policy versions')
            ->assertSee('Policy v1')
            ->assertSee('Policy v2');
    }

    public function test_privacy_policy_version_page_renders(): void
    {
        PrivacyPolicy::create([
            'version' => '2025-01',
            'title' => 'Policy January 2025',
            'content' => 'Policy content',
            'is_active' => true,
            'is_current' => false,
            'effective_date' => now(),
        ]);

        $this->get(route('gdpr.privacy-policy.version', '2025-01'))
            ->assertOk()
            ->assertSee('Policy January 2025');
    }

    public function test_privacy_settings_requires_authentication(): void
    {
        $this->get(route('gdpr.privacy-settings.index'))
            ->assertRedirect();
    }

    public function test_privacy_settings_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('gdpr.privacy-settings.index'))
            ->assertOk()
            ->assertSee('Privacy Settings');
    }

    public function test_gdpr_request_create_page_renders_with_type_from_query_string(): void
    {
        $this->get(route('gdpr.request.create', ['type' => 'deletion']))
            ->assertOk()
            ->assertSee('Request Data Deletion');
    }

    public function test_cookie_consent_show_returns_default_not_consented_state(): void
    {
        $this->getJson(route('gdpr.cookie-consent.show'))
            ->assertOk()
            ->assertJson([
                'consent' => null,
                'has_consented' => false,
            ]);
    }

    public function test_cookie_consent_store_creates_consent_and_tracking_and_sets_cookie(): void
    {
        $response = $this->postJson(route('gdpr.cookie-consent.store'), [
            'analytics' => true,
            'marketing' => false,
            'preferences' => true,
            'consent_method' => 'api',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertCookie('cookie_consent');

        $this->assertDatabaseCount('cookie_consents', 1);
        $this->assertDatabaseHas('cookie_consents', [
            'analytics' => 1,
            'marketing' => 0,
            'preferences' => 1,
        ]);

        $this->assertDatabaseCount('consent_tracking', 3);
        $this->assertTrue(
            ConsentTracking::where('consent_method', 'api')->count() >= 3,
            'Expected consent tracking rows to be recorded with method "api".'
        );
    }
}

