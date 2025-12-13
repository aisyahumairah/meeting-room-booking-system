<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>User Activity Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .period {
            color: #666;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .highlight {
            color: #e67e22;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>User Activity Report</h1>
        <div class="period">Period: {{ $startDate }} to {{ $endDate }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th class="text-center">Total Bookings</th>
                <th class="text-center">Cancelled</th>
                <th class="text-center">Cancellation Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user['name'] }}</td>
                    <td>{{ $user['email'] }}</td>
                    <td>{{ $user['department'] ?? '-' }}</td>
                    <td class="text-center">{{ $user['total_bookings'] }}</td>
                    <td class="text-center">{{ $user['cancelled_bookings'] }}</td>
                    <td class="text-center {{ $user['cancellation_rate'] > 20 ? 'highlight' : '' }}">
                        {{ $user['cancellation_rate'] }}%
                    </td>
                </tr>
            @endforeach
            @if (empty($users))
                <tr>
                    <td colspan="6" class="text-center">No data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} | MRBS - Meeting Room Booking System
    </div>
</body>

</html>
