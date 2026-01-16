<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Booking Cancelled - MRBS</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #ff3e1d;">Booking Cancelled</h2>

        <p>Your meeting room booking has been cancelled.</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin-bottom: 10px;"><strong>Cancelled Booking Details:</strong></p>
            <ul style="list-style-type: none; padding-left: 0; margin: 0;">
                <li style="margin-bottom: 5px;"><strong>Reference:</strong> {{ $booking->reference_number }}</li>
                <li style="margin-bottom: 5px;"><strong>Room:</strong> {{ $booking->room->name }}</li>
                <li style="margin-bottom: 5px;"><strong>Date:</strong> {{ $booking->booking_date->format('l, F j, Y') }}
                </li>
                <li style="margin-bottom: 5px;"><strong>Time:</strong>
                    {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
                    {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</li>
                <li style="margin-bottom: 5px;"><strong>Purpose:</strong> {{ $booking->purpose }}</li>
            </ul>
        </div>

        @if ($reason)
            <div
                style="background-color: #fff2f0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff3e1d;">
                <p style="margin: 0;"><strong>Cancellation Reason:</strong></p>
                <p style="margin: 5px 0 0 0;">{{ $reason }}</p>
            </div>
        @endif

        <p>If you need to book a room again, please visit the booking system.</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ route('rooms.index') }}"
                style="background-color: #696cff; color: white; padding: 12px 30px;
                      text-decoration: none; border-radius: 5px; display: inline-block;">
                Browse Rooms
            </a>
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #666; font-size: 12px;">
            This is an automated message from the Meeting Room Booking System ({{ config('app.name') }}).
        </p>
    </div>
</body>

</html>
