<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CheckoutLockResource;
use App\Filament\Resources\CheckoutLockResource\Pages\ListCheckoutLocks;
use App\Models\CheckoutLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Lunar\Models\Cart;
use Tests\TestCase;

class FilamentCheckoutLockResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_lock_index_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $cart = Cart::factory()->create();

        $lock = CheckoutLock::create([
            'cart_id' => $cart->id,
            'session_id' => 'sess-' . Str::lower(Str::random(12)),
            'state' => CheckoutLock::STATE_PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);

        $slug = CheckoutLockResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $lock->getKey(),
        ]))->assertOk();
    }

    public function test_checkout_lock_can_be_released_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $cart = Cart::factory()->create();

        $lock = CheckoutLock::create([
            'cart_id' => $cart->id,
            'session_id' => 'sess-' . Str::lower(Str::random(12)),
            'state' => CheckoutLock::STATE_PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);

        Livewire::test(ListCheckoutLocks::class)
            ->callTableAction('release', $lock);

        $lock = $lock->fresh();
        $this->assertSame(CheckoutLock::STATE_FAILED, $lock->state);
    }
}

