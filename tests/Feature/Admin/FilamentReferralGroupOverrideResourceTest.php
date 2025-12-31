<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ReferralGroupOverrideResource;
use App\Filament\Resources\ReferralGroupOverrideResource\Pages\CreateReferralGroupOverride;
use App\Models\ReferralGroupOverride;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentReferralGroupOverrideResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_group_override_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $group = UserGroup::query()->create([
            'name' => 'VIP',
            'type' => UserGroup::TYPE_VIP,
        ]);

        $override = ReferralGroupOverride::query()->create([
            'user_group_id' => $group->id,
            'enabled' => true,
        ]);

        $slug = ReferralGroupOverrideResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $override->getKey()]))->assertOk();
    }

    public function test_referral_group_override_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $group = UserGroup::query()->create([
            'name' => 'B2B',
            'type' => UserGroup::TYPE_B2B,
        ]);

        Livewire::test(CreateReferralGroupOverride::class)
            ->set('data.user_group_id', $group->id)
            ->set('data.enabled', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new ReferralGroupOverride())->getTable(), 1);
    }
}

