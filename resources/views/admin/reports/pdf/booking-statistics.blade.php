<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Booking Statistics Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
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
            margin-top: 10px;
            margin-bottom: 20px;
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

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Booking Statistics Report</h1>
        <div class="period">Period: {{ $startDate }} to {{ $endDate }}</div>
    </div>

    <h2>Status Distribution</h2>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th class="text-center">Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($statusDistribution as $status => $count)
                <tr>
                    <td>{{ ucfirst($status) }}</td>
                    <td class="text-center">{{ $count }}</td>
                </tr>
            @endforeach
            @if (empty($statusDistribution))
                <tr>
                    <td colspan="2" class="text-center">No data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <h2>Top Booked Rooms</h2>
    <table>
        <thead>
            <tr>
                <th>Room Name</th>
                <th class="text-center">Booking Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($topRooms as $room)
                <tr>
                    <td>{{ $room['room']['name'] ?? 'Unknown' }}</td>
                    <td class="text-center">{{ $room['booking_count'] }}</td>
                </tr>
            @endforeach
            @if (empty($topRooms))
                <tr>
                    <td colspan="2" class="text-center">No data available</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} | MRBS - Meeting Room Booking System
    </div>
</body>

</html>
