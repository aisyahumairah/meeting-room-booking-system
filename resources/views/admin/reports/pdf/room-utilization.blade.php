<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Room Utilization Report</title>
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

        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Room Utilization Report</h1>
        <div class="period">Period: {{ $startDate }} to {{ $endDate }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Room Name</th>
                <th>Location</th>
                <th class="text-center">Capacity</th>
                <th class="text-center">Total Bookings</th>
                <th class="text-center">Hours Used</th>
                <th class="text-center">Utilization Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData as $data)
                <tr>
                    <td>{{ $data['room_name'] }}</td>
                    <td>{{ $data['location'] }}</td>
                    <td class="text-center">{{ $data['capacity'] }}</td>
                    <td class="text-center">{{ $data['total_bookings'] }}</td>
                    <td class="text-center">{{ $data['total_hours'] }}h</td>
                    <td class="text-center">{{ $data['utilization_rate'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('Y-m-d H:i:s') }} | MRBS - Meeting Room Booking System
    </div>
</body>

</html>
