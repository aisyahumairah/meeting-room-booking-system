<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['status' => 'active', 'role' => 'administrator']);
        $this->regularUser = User::factory()->create(['status' => 'active', 'role' => 'regular_user']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_admin_can_view_all_bookings()
    {
        Booking::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.bookings.index');
        $response->assertViewHas('bookings');
    }

    public function test_regular_user_cannot_access_admin_bookings()
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.bookings.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_filter_by_status()
    {
        Booking::factory()->count(3)->create(['status' => 'confirmed']);
        Booking::factory()->cancelled()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index', ['status' => 'cancelled']));

        $response->assertViewHas('bookings', function ($bookings) {
            return $bookings->count() === 2;
        });
    }

    public function test_admin_can_search_by_reference()
    {
        $booking = Booking::factory()->create(['reference_number' => 'BK-2025-00123']);
        Booking::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index', ['search' => 'BK-2025-00123']));

        $response->assertViewHas('bookings', function ($bookings) use ($booking) {
            return $bookings->count() === 1 && $bookings->first()->id === $booking->id;
        });
    }

    public function test_admin_can_view_any_booking_detail()
    {
        $booking = Booking::factory()->create(['user_id' => $this->regularUser->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $booking));

        $response->assertStatus(200);
        $response->assertSee($booking->reference_number);
    }

    public function test_admin_can_cancel_any_booking()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->regularUser->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.bookings.destroy', $booking), [
                'cancellation_reason' => 'Room needed for priority event',
            ]);

        $response->assertRedirect(route('admin.bookings.index'));
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancelled_by' => $this->admin->id,
        ]);
    }

    public function test_stats_are_displayed()
    {
        Booking::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.bookings.index'));

        $response->assertViewHas('stats');
    }
}
