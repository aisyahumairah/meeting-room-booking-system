<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AuditLogExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = AuditLog::query();

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['actor_id'])) {
            $query->where('actor_id', $this->filters['actor_id']);
        }
        if (!empty($this->filters['event_type'])) {
            $query->where('event_type', $this->filters['event_type']);
        }
        if (!empty($this->filters['target_type'])) {
            $query->where('target_type', $this->filters['target_type']);
        }
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'ilike', "%{$search}%")
                    ->orWhereRaw("details::text ilike ?", ["%{$search}%"]);
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Event ID',
            'Timestamp',
            'Actor ID',
            'Actor Name',
            'Event Type',
            'Target Type',
            'Target ID',
            'Details',
            'IP Address',
            'User Agent',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->actor_id,
            $log->actor_name,
            $log->event_type,
            $log->target_type,
            $log->target_id,
            is_array($log->details) ? json_encode($log->details) : $log->details,
            $log->ip_address,
            $log->user_agent,
        ];
    }
}
