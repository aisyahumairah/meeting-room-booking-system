<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Room Status Changed - MRBS</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #ff3e1d;">Room Status Changed</h2>

        <p>The status of <strong>{{ $room->name }}</strong> has been updated.</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p style="margin-bottom: 15px;"><strong>Status Change:</strong></p>
            <ul style="list-style-type: none; padding-left: 0; margin: 0;">
                <li style="margin-bottom: 5px;"><strong>Previous Status:</strong> {{ ucfirst($oldStatus) }}</li>
                <li style="margin-bottom: 5px;"><strong>New Status:</strong> {{ ucfirst($newStatus) }}</li>
            </ul>
        </div>

        @if (count($affectedBookings) > 0)
            <h3 style="color: #696cff; font-size: 18px; margin-top: 30px;">Your Affected Bookings:</h3>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #f3f3f3;">
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Date</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Time</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($affectedBookings as $booking)
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                {{ \Carbon\Carbon::parse($booking['booking_date'])->format('M d, Y') }}</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                {{ \Carbon\Carbon::parse($booking['start_time'])->format('g:i A') }}</td>
                            <td style="padding: 10px; border: 1px solid #ddd;">{{ $booking['reference_number'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($newStatus === 'under_maintenance' || $newStatus === 'maintenance')
                <div
                    style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <strong>Note:</strong> Your bookings in this room may be affected. Please check your booking status
                    or contact an administrator for assistance.
                </div>
            @endif
        @endif

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ route('rooms.show', $room) }}"
                style="background-color: #696cff; color: white; padding: 12px 30px;
                      text-decoration: none; border-radius: 5px; display: inline-block;">
                View Room Details
            </a>
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">

        <p style="color: #666; font-size: 12px;">
            This is an automated message from the Meeting Room Booking System ({{ config('app.name') }}).
        </p>
    </div>
</body>

</html>
