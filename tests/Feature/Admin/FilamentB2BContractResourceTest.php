<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\B2BContractResource;
use App\Filament\Resources\B2BContractResource\Pages\CreateB2BContract;
use App\Models\B2BContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Lunar\Models\Customer;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentB2BContractResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_b2b_contract_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $customer = Customer::factory()->create([
            'company_name' => 'Acme Inc',
        ]);

        $contract = B2BContract::query()->create([
            'contract_id' => 'C-' . Str::upper(Str::random(8)),
            'customer_id' => $customer->id,
            'name' => 'Test Contract',
            'valid_from' => now()->toDateString(),
            'status' => B2BContract::STATUS_DRAFT,
            'approval_state' => B2BContract::APPROVAL_PENDING,
            'priority' => 0,
        ]);

        $slug = B2BContractResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $contract->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $contract->getKey()]))->assertOk();
    }

    public function test_b2b_contract_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $customer = Customer::factory()->create([
            'company_name' => 'Acme Inc',
        ]);

        Livewire::test(CreateB2BContract::class)
            ->set('data.contract_id', 'C-' . Str::upper(Str::random(8)))
            ->set('data.customer_id', $customer->id)
            ->set('data.name', 'Created via Filament')
            ->set('data.valid_from', now()->toDateString())
            ->set('data.status', B2BContract::STATUS_DRAFT)
            ->set('data.approval_state', B2BContract::APPROVAL_PENDING)
            ->set('data.priority', 0)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new B2BContract())->getTable(), 1);
    }
}

