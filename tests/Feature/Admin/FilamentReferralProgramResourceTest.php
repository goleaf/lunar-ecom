<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ReferralProgramResource;
use App\Filament\Resources\ReferralProgramResource\Pages\CreateReferralProgram;
use App\Models\ReferralProgram;
use App\Models\ReferralRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentReferralProgramResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_program_index_create_view_edit_and_analytics_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $program = ReferralProgram::query()->create([
            'name' => 'Test Referral Program',
            'handle' => 'test-program-' . Str::lower(Str::random(8)),
            'status' => ReferralProgram::STATUS_DRAFT,
            'currency_scope' => ReferralProgram::CURRENCY_SCOPE_ALL,
            'audience_scope' => ReferralProgram::AUDIENCE_SCOPE_ALL,
            'last_click_wins' => true,
            'attribution_ttl_days' => 7,
            'referral_code_validity_days' => 365,
            'default_stacking_mode' => ReferralRule::STACKING_EXCLUSIVE,
        ]);

        $slug = ReferralProgramResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $program->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $program->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.analytics", ['record' => $program->getKey()]))->assertOk();
    }

    public function test_referral_program_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        Livewire::test(CreateReferralProgram::class)
            ->set('data.name', 'Created Referral Program')
            ->set('data.handle', 'created-program-' . Str::lower(Str::random(8)))
            ->set('data.status', ReferralProgram::STATUS_DRAFT)
            ->set('data.currency_scope', ReferralProgram::CURRENCY_SCOPE_ALL)
            ->set('data.audience_scope', ReferralProgram::AUDIENCE_SCOPE_ALL)
            ->set('data.last_click_wins', true)
            ->set('data.attribution_ttl_days', 7)
            ->set('data.referral_code_validity_days', 365)
            ->set('data.default_stacking_mode', ReferralRule::STACKING_EXCLUSIVE)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new ReferralProgram())->getTable(), 1);
    }
}

