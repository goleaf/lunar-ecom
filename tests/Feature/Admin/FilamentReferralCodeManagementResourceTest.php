<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ReferralCodeManagementResource;
use App\Filament\Resources\ReferralCodeManagementResource\Pages\ManageReferralCodes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentReferralCodeManagementResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_code_management_index_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $user = User::factory()->create();

        $slug = ReferralCodeManagementResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $user->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $user->getKey()]))->assertOk();
    }

    public function test_referral_code_can_be_generated_and_regenerated_via_table_actions(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $user = User::withoutEvents(fn () => User::factory()->create([
            'referral_code' => null,
            'referral_link_slug' => null,
        ]));

        Livewire::test(ManageReferralCodes::class)
            ->callTableAction('generate_code', $user);

        $user = $user->fresh();
        $this->assertNotNull($user->referral_code);

        $oldCode = $user->referral_code;

        Livewire::test(ManageReferralCodes::class)
            ->callTableAction('regenerate_code', $user);

        $user = $user->fresh();
        $this->assertNotNull($user->referral_code);
        $this->assertNotEquals($oldCode, $user->referral_code);
    }
}

