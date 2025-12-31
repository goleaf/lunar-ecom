<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ReferralAttributionResource;
use App\Filament\Resources\ReferralAttributionResource\Pages\CreateReferralAttribution;
use App\Filament\Resources\ReferralAttributionResource\Pages\ListReferralAttributions;
use App\Models\ReferralAttribution;
use App\Models\ReferralProgram;
use App\Models\ReferralRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentReferralAttributionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_attribution_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $referee = User::factory()->create();
        $referrer = User::factory()->create();

        $program = ReferralProgram::query()->create([
            'name' => 'Test Program',
            'handle' => 'test-program-' . Str::lower(Str::random(8)),
            'status' => ReferralProgram::STATUS_DRAFT,
            'currency_scope' => ReferralProgram::CURRENCY_SCOPE_ALL,
            'audience_scope' => ReferralProgram::AUDIENCE_SCOPE_ALL,
            'last_click_wins' => true,
            'attribution_ttl_days' => 7,
            'referral_code_validity_days' => 365,
            'default_stacking_mode' => ReferralRule::STACKING_EXCLUSIVE,
        ]);

        $attribution = ReferralAttribution::query()->create([
            'referee_user_id' => $referee->id,
            'referrer_user_id' => $referrer->id,
            'program_id' => $program->id,
            'code_used' => 'TESTCODE',
            'attributed_at' => now(),
            'attribution_method' => ReferralAttribution::METHOD_CODE,
            'status' => ReferralAttribution::STATUS_PENDING,
        ]);

        $slug = ReferralAttributionResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $attribution->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $attribution->getKey()]))->assertOk();
    }

    public function test_referral_attribution_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $referee = User::factory()->create();
        $referrer = User::factory()->create();

        $program = ReferralProgram::query()->create([
            'name' => 'Create Program',
            'handle' => 'create-program-' . Str::lower(Str::random(8)),
            'status' => ReferralProgram::STATUS_DRAFT,
            'currency_scope' => ReferralProgram::CURRENCY_SCOPE_ALL,
            'audience_scope' => ReferralProgram::AUDIENCE_SCOPE_ALL,
            'last_click_wins' => true,
            'attribution_ttl_days' => 7,
            'referral_code_validity_days' => 365,
            'default_stacking_mode' => ReferralRule::STACKING_EXCLUSIVE,
        ]);

        Livewire::test(CreateReferralAttribution::class)
            ->set('data.referee_user_id', $referee->id)
            ->set('data.referrer_user_id', $referrer->id)
            ->set('data.program_id', $program->id)
            ->set('data.code_used', 'CREATEDCODE')
            ->set('data.attribution_method', ReferralAttribution::METHOD_CODE)
            ->set('data.status', ReferralAttribution::STATUS_PENDING)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new ReferralAttribution())->getTable(), 1);
        $this->assertNotNull(ReferralAttribution::query()->first()?->attributed_at);
    }

    public function test_referral_attribution_can_be_confirmed_and_rejected_via_table_actions(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $referee = User::factory()->create();
        $referrer = User::factory()->create();

        $program = ReferralProgram::query()->create([
            'name' => 'Actions Program',
            'handle' => 'actions-program-' . Str::lower(Str::random(8)),
            'status' => ReferralProgram::STATUS_DRAFT,
            'currency_scope' => ReferralProgram::CURRENCY_SCOPE_ALL,
            'audience_scope' => ReferralProgram::AUDIENCE_SCOPE_ALL,
            'last_click_wins' => true,
            'attribution_ttl_days' => 7,
            'referral_code_validity_days' => 365,
            'default_stacking_mode' => ReferralRule::STACKING_EXCLUSIVE,
        ]);

        $toConfirm = ReferralAttribution::query()->create([
            'referee_user_id' => $referee->id,
            'referrer_user_id' => $referrer->id,
            'program_id' => $program->id,
            'code_used' => 'CONFIRMCODE',
            'attributed_at' => now(),
            'attribution_method' => ReferralAttribution::METHOD_LINK,
            'status' => ReferralAttribution::STATUS_PENDING,
        ]);

        Livewire::test(ListReferralAttributions::class)
            ->callTableAction('confirm', $toConfirm);

        $this->assertDatabaseHas((new ReferralAttribution())->getTable(), [
            'id' => $toConfirm->id,
            'status' => ReferralAttribution::STATUS_CONFIRMED,
        ]);

        $toReject = ReferralAttribution::query()->create([
            'referee_user_id' => $referee->id,
            'referrer_user_id' => $referrer->id,
            'program_id' => $program->id,
            'code_used' => 'REJECTCODE',
            'attributed_at' => now(),
            'attribution_method' => ReferralAttribution::METHOD_LINK,
            'status' => ReferralAttribution::STATUS_PENDING,
        ]);

        Livewire::test(ListReferralAttributions::class)
            ->callTableAction('reject', $toReject, [
                'reason' => 'Test rejection',
            ]);

        $this->assertDatabaseHas((new ReferralAttribution())->getTable(), [
            'id' => $toReject->id,
            'status' => ReferralAttribution::STATUS_REJECTED,
            'rejection_reason' => 'Test rejection',
        ]);
    }
}

