<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ReferralUserOverrideResource;
use App\Filament\Resources\ReferralUserOverrideResource\Pages\CreateReferralUserOverride;
use App\Models\ReferralUserOverride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentReferralUserOverrideResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_user_override_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $user = User::factory()->create();

        $override = ReferralUserOverride::query()->create([
            'user_id' => $user->id,
            'block_referrals' => false,
        ]);

        $slug = ReferralUserOverrideResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $override->getKey()]))->assertOk();
    }

    public function test_referral_user_override_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $user = User::factory()->create();

        Livewire::test(CreateReferralUserOverride::class)
            ->set('data.user_id', $user->id)
            ->set('data.block_referrals', false)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new ReferralUserOverride())->getTable(), 1);
    }
}

