<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BookingReportExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    protected string $startDate;
    protected string $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        return Booking::query()
            ->whereBetween('booking_date', [$this->startDate, $this->endDate])
            ->with(['user:id,name,department', 'room:id,name'])
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    public function headings(): array
    {
        return [
            'Reference',
            'Date',
            'Start Time',
            'End Time',
            'Room',
            'Booked By',
            'Department',
            'Purpose',
            'Attendees',
            'Status',
            'Created At',
        ];
    }

    public function map($booking): array
    {
        return [
            $booking->reference_number,
            $booking->booking_date->format('Y-m-d'),
            $booking->start_time, // String format H:i:s
            $booking->end_time,   // String format H:i:s
            $booking->room->name ?? 'N/A',
            $booking->user->name ?? 'N/A',
            $booking->user->department ?? '-',
            $booking->purpose,
            $booking->attendees ?? '-',
            ucfirst($booking->status),
            $booking->created_at->format('Y-m-d H:i'),
        ];
    }

    public function title(): string
    {
        return 'Booking Report';
    }
}
