<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Booking;
use App\Models\User;
use App\Models\Room;
use App\Services\BookingStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class BookingStatusAutoUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_past_booking_is_marked_as_completed()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
    }

    public function test_future_booking_is_not_changed()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_cancelled_booking_is_not_changed()
    {
        $booking = Booking::factory()->cancelled()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    public function test_already_completed_booking_is_not_updated()
    {
        $booking = Booking::factory()->completed()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $originalUpdatedAt = $booking->updated_at;

        // Wait a moment
        sleep(1);

        Artisan::call('bookings:complete-expired');

        $booking->refresh();
        // Updated_at should roughly match (it might change if other fields were touched, but status shouldn't trigger update)
        // Actually, if we touch it, it will update.
        // The service logic: if ($booking->status !== 'confirmed') { return false; }
        // So no update should happen.
        $this->assertEquals($originalUpdatedAt->timestamp, $booking->updated_at->timestamp);
    }

    public function test_dry_run_does_not_update()
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
        ]);

        Artisan::call('bookings:complete-expired', ['--dry-run' => true]);

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
    }

    public function test_service_correctly_identifies_expired_bookings()
    {
        // Create various bookings
        $pastConfirmed = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $futureConfirmed = Booking::factory()->create([
            'status' => 'confirmed',
            'booking_date' => now()->addDays(1)->format('Y-m-d'),
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $pastCancelled = Booking::factory()->cancelled()->create([
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $service = app(BookingStatusService::class);
        $expired = $service->getExpiredBookings();

        $this->assertCount(1, $expired);
        $this->assertEquals($pastConfirmed->id, $expired->first()->id);
    }

    public function test_command_outputs_correct_count()
    {
        Booking::factory()->count(3)->create([
            'status' => 'confirmed',
            'booking_date' => now()->subDays(1)->format('Y-m-d'),
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);

        $this->artisan('bookings:complete-expired')
            ->expectsOutputToContain('3 booking(s)')
            ->assertSuccessful();
    }
}
