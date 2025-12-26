<?php

namespace Tests\Feature\Storefront;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Language;
use Tests\TestCase;

class ReferralLandingLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Live locale resolution depends on Lunar languages being present.
        Language::firstOrCreate(['code' => 'en'], ['name' => 'English', 'default' => true]);
        Language::where('code', 'en')->update(['default' => true]);

        Language::firstOrCreate(['code' => 'ru'], ['name' => 'Russian', 'default' => false]);
    }

    public function test_short_referral_url_redirects_to_cookie_locale(): void
    {
        $user = User::factory()->create([
            'referral_code' => 'ABC12345',
            'referral_link_slug' => 'abc12345',
            'status' => 'active',
            'referral_blocked' => false,
        ]);

        $response = $this
            ->withCookie('site_locale', 'ru')
            ->get('/r/' . $user->referral_code . '?utm_source=test');

        $response->assertRedirect('/ru/r/' . $user->referral_code . '?utm_source=test');
    }

    public function test_localized_referral_page_renders_and_sets_attribution_cookies(): void
    {
        $user = User::factory()->create([
            'referral_code' => 'ABC12345',
            'referral_link_slug' => 'abc12345',
            'status' => 'active',
            'referral_blocked' => false,
        ]);

        $response = $this->get('/en/r/' . $user->referral_code);

        $response->assertOk();
        $response->assertSee("You've been invited", false);

        // Legacy + required attribution cookies
        $response->assertCookie('referral_code', $user->referral_code);
        $response->assertCookie('referral_referrer_id', (string) $user->id);
        $response->assertCookie('referral_attributed_at');
        $response->assertCookie('referral_locale_seen');
    }
}


