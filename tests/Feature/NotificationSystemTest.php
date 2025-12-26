<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Booking;
use App\Models\Room;
use App\Models\SystemSetting;
use App\Models\NotificationLog;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;
use App\Mail\WelcomeEmail;
use App\Mail\BookingConfirmedEmail;
use App\Mail\BookingCancelledEmail;
use App\Mail\BookingReminderEmail;
use App\Mail\RoomStatusChangedEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->seed(\Database\Seeders\SystemSettingSeeder::class);

        $this->user = User::factory()->passwordChanged()->create();
        $this->room = Room::factory()->create();
        $this->booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);
    }

    public function test_welcome_email_is_sent_and_logged(): void
    {
        NotificationService::sendWelcomeEmail($this->user, 'temp123');

        Mail::assertQueued(WelcomeEmail::class);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_WELCOME,
            'status' => NotificationLog::STATUS_SENT,
        ]);
    }

    public function test_booking_confirmed_email_is_sent_and_logged(): void
    {
        NotificationService::sendBookingConfirmedEmail($this->booking);

        Mail::assertQueued(BookingConfirmedEmail::class);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_BOOKING_CONFIRMED,
            'status' => NotificationLog::STATUS_SENT,
        ]);
    }

    public function test_booking_confirmed_email_respects_user_preference(): void
    {
        // Disable user preference
        UserNotificationPreference::create([
            'user_id' => $this->user->id,
            'booking_confirmed' => false,
            'booking_cancelled' => true,
            'booking_reminder' => true,
            'room_status_changed' => true,
        ]);

        NotificationService::sendBookingConfirmedEmail($this->booking);

        Mail::assertNotQueued(BookingConfirmedEmail::class);
    }

    public function test_booking_cancelled_email_is_sent_and_logged(): void
    {
        NotificationService::sendBookingCancelledEmail($this->booking, 'Meeting cancelled');

        Mail::assertQueued(BookingCancelledEmail::class);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_BOOKING_CANCELLED,
            'status' => NotificationLog::STATUS_SENT,
        ]);
    }

    public function test_booking_reminder_email_is_sent_and_logged(): void
    {
        NotificationService::sendBookingReminderEmail($this->booking);

        Mail::assertQueued(BookingReminderEmail::class);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_BOOKING_REMINDER,
            'status' => NotificationLog::STATUS_SENT,
        ]);
    }

    public function test_notifications_respect_master_toggle(): void
    {
        SystemSetting::set('email_enabled', 'false', 'bool');

        NotificationService::sendBookingConfirmedEmail($this->booking);

        Mail::assertNotQueued(BookingConfirmedEmail::class);
    }

    public function test_notifications_respect_individual_system_toggle(): void
    {
        SystemSetting::set('notify_booking_confirmed', 'false', 'bool');

        NotificationService::sendBookingConfirmedEmail($this->booking);

        Mail::assertNotQueued(BookingConfirmedEmail::class);
    }

    public function test_user_can_view_notification_preferences(): void
    {
        $response = $this->actingAs($this->user)->get(route('profile.notifications'));

        $response->assertStatus(200);
        $response->assertSee('Notification Preferences');
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->user)->put(route('profile.notifications.update'), [
            'booking_confirmed' => false,
            'booking_cancelled' => true,
            'booking_reminder' => false,
            'room_status_changed' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $this->user->id,
            'booking_confirmed' => false,
            'booking_reminder' => false,
        ]);
    }

    public function test_notification_log_records_metadata(): void
    {
        NotificationService::sendBookingConfirmedEmail($this->booking);

        $log = NotificationLog::where('user_id', $this->user->id)
            ->where('type', NotificationLog::TYPE_BOOKING_CONFIRMED)
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('booking_id', $log->metadata);
        $this->assertEquals($this->booking->id, $log->metadata['booking_id']);
    }

    public function test_room_status_notification_sent_to_affected_users(): void
    {
        $bookings = [
            [
                'user_id' => $this->user->id,
                'reference_number' => 'BK001',
                'booking_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
            ],
        ];

        NotificationService::sendRoomStatusChangedEmail($this->room, 'available', 'maintenance', $bookings);

        Mail::assertQueued(RoomStatusChangedEmail::class);
    }

    public function test_welcome_email_respects_system_toggle(): void
    {
        SystemSetting::set('notify_welcome_email', 'false', 'bool');

        NotificationService::sendWelcomeEmail($this->user, 'temp123');

        Mail::assertNotQueued(WelcomeEmail::class);
    }

    public function test_notification_log_type_name_accessor(): void
    {
        $log = NotificationLog::create([
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_BOOKING_CONFIRMED,
            'channel' => 'email',
            'recipient_email' => $this->user->email,
            'subject' => 'Test',
            'status' => NotificationLog::STATUS_SENT,
        ]);

        $this->assertEquals('Booking Confirmed', $log->type_name);
    }

    public function test_scopes_work_correctly(): void
    {
        NotificationLog::create([
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_WELCOME,
            'channel' => 'email',
            'recipient_email' => $this->user->email,
            'subject' => 'Test',
            'status' => NotificationLog::STATUS_SENT,
        ]);

        NotificationLog::create([
            'user_id' => $this->user->id,
            'type' => NotificationLog::TYPE_BOOKING_CONFIRMED,
            'channel' => 'email',
            'recipient_email' => $this->user->email,
            'subject' => 'Test',
            'status' => NotificationLog::STATUS_FAILED,
            'error_message' => 'SMTP error',
        ]);

        $this->assertEquals(1, NotificationLog::ofType(NotificationLog::TYPE_WELCOME)->count());
        $this->assertEquals(1, NotificationLog::failed()->count());
    }
}
