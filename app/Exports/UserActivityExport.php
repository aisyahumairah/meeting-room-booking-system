<?php

namespace App\Exports;

use App\Models\AuditLog;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class UserActivityExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithTitle
{
    protected User $user;
    protected array $filters;

    public function __construct(User $user, array $filters = [])
    {
        $this->user = $user;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return "Activity - {$this->user->name}";
    }

    public function query()
    {
        $query = AuditLog::where('actor_id', $this->user->id);

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['event_type'])) {
            $query->where('event_type', $this->filters['event_type']);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Timestamp',
            'Event Type',
            'Target Type',
            'Target ID',
            'Details',
            'IP Address',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->event_type,
            $log->target_type,
            $log->target_id,
            is_array($log->details) ? json_encode($log->details) : $log->details,
            $log->ip_address,
        ];
    }
}
