<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\AvailabilityBookingResource;
use App\Filament\Resources\AvailabilityBookingResource\Pages\ListAvailabilityBookings;
use App\Models\AvailabilityBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAvailabilityBookingResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_booking_index_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $booking = AvailabilityBooking::factory()->create();

        $slug = AvailabilityBookingResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $booking->getKey()]))->assertOk();
    }

    public function test_availability_booking_can_be_confirmed_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $booking = AvailabilityBooking::factory()->pending()->create();

        Livewire::test(ListAvailabilityBookings::class)
            ->callTableAction('confirm', $booking);

        $this->assertDatabaseHas((new AvailabilityBooking())->getTable(), [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }
}

