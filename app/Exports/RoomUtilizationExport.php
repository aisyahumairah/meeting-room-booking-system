<?php

namespace App\Exports;

use App\Models\Room;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoomUtilizationExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    protected string $startDate;
    protected string $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $rooms = Room::where('status', 'active')->get();

        return $rooms->map(function ($room) {
            $bookings = $room->bookings()
                ->whereBetween('booking_date', [$this->startDate, $this->endDate])
                ->where('status', '!=', 'cancelled')
                ->get();

            $totalHours = $bookings->sum(function ($booking) {
                return Carbon::parse($booking->start_time)->diffInMinutes(Carbon::parse($booking->end_time)) / 60;
            });

            $totalDays = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;
            $workingDays = $this->countWorkingDays($this->startDate, $this->endDate);
            $availableHours = $workingDays * 10;
            $utilizationRate = $availableHours > 0 ? min(round(($totalHours / $availableHours) * 100, 1), 100) : 0;

            return [
                'room_name' => $room->name,
                'location' => $room->floor_location,
                'capacity' => $room->capacity,
                'total_bookings' => $bookings->count(),
                'total_hours' => round($totalHours, 1),
                'available_hours' => $availableHours,
                'utilization_rate' => $utilizationRate . '%',
            ];
        })->sortByDesc('total_bookings');
    }

    public function headings(): array
    {
        return [
            'Room Name',
            'Location',
            'Capacity',
            'Total Bookings',
            'Hours Used',
            'Available Hours',
            'Utilization Rate',
        ];
    }

    public function title(): string
    {
        return 'Room Utilization';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function countWorkingDays(string $startDate, string $endDate): int
    {
        $count = 0;
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            if (!$current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
