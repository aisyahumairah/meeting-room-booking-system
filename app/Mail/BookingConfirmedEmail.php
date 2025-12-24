<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Booking Confirmed: {$this->booking->reference_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-confirmed',
            with: [
                'booking' => $this->booking->load(['room', 'user']),
                'viewUrl' => route('bookings.show', $this->booking),
            ],
        );
    }
}
