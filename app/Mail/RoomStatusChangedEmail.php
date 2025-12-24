<?php

namespace App\Mail;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RoomStatusChangedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Room $room,
        public string $oldStatus,
        public string $newStatus,
        public array $affectedBookings = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Room Status Changed: {$this->room->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.room-status-changed',
            with: [
                'room' => $this->room,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'affectedBookings' => $this->affectedBookings,
            ],
        );
    }
}
