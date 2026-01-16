<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Upcoming Meeting Reminder - MRBS</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #696cff;">Upcoming Meeting Reminder</h2>

        <p>This is a reminder that you have a meeting room booking tomorrow.</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin-bottom: 10px;"><strong>Booking Details:</strong></p>
            <ul style="list-style-type: none; padding-left: 0; margin: 0;">
                <li style="margin-bottom: 5px;"><strong>Reference:</strong> {{ $booking->reference_number }}</li>
                <li style="margin-bottom: 5px;"><strong>Room:</strong> {{ $booking->room->name }}</li>
                <li style="margin-bottom: 5px;"><strong>Location:</strong> {{ $booking->room->location ?? 'N/A' }}</li>
                <li style="margin-bottom: 5px;"><strong>Date:</strong> {{ $booking->booking_date->format('l, F j, Y') }}
                </li>
                <li style="margin-bottom: 5px;"><strong>Time:</strong>
                    {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
                    {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</li>
                <li style="margin-bottom: 5px;"><strong>Purpose:</strong> {{ $booking->purpose }}</li>
                @if ($booking->room->amenities->isNotEmpty())
                    <li style="margin-bottom: 5px;"><strong>Room Amenities:</strong>
                        {{ $booking->room->amenities->pluck('name')->join(', ') }}</li>
                @endif
            </ul>
        </div>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $viewUrl }}"
                style="background-color: #696cff; color: white; padding: 12px 30px;
                      text-decoration: none; border-radius: 5px; display: inline-block;">
                View Booking Details
            </a>
        </p>

        <p style="color: #666; font-size: 14px;">
            If you need to cancel this booking, please do so before the meeting time.
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #666; font-size: 12px;">
            This is an automated message from the Meeting Room Booking System ({{ config('app.name') }}).
        </p>
    </div>
</body>

</html>
