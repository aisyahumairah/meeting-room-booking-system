<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UserActivityReportExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
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
        return User::withCount(['bookings as total_bookings' => function ($query) {
            $query->whereBetween('booking_date', [$this->startDate, $this->endDate]);
        }])
            ->withCount(['bookings as cancelled_bookings' => function ($query) {
                $query->whereBetween('booking_date', [$this->startDate, $this->endDate])->where('status', 'cancelled');
            }])
            ->orderByDesc('total_bookings')
            ->get()
            ->map(function ($user) {
                $cancellationRate = $user->total_bookings > 0
                    ? round(($user->cancelled_bookings / $user->total_bookings) * 100, 1) : 0;

                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department ?? '-',
                    'role' => ucfirst(str_replace('_', ' ', $user->role)),
                    'total_bookings' => $user->total_bookings,
                    'cancelled_bookings' => $user->cancelled_bookings,
                    'cancellation_rate' => $cancellationRate . '%',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Department',
            'Role',
            'Total Bookings',
            'Cancelled',
            'Cancellation Rate',
        ];
    }

    public function title(): string
    {
        return 'User Activity';
    }
}
