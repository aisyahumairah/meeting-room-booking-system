<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AuditLogExport;

class AuditLogController extends Controller
{
    /**
     * Display the audit log list with filters.
     */
    public function index(Request $request)
    {
        $query = AuditLog::query();

        // Date range filter
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Date presets
        if ($preset = $request->input('preset')) {
            $this->applyDatePreset($query, $preset);
        }

        // Actor filter
        if ($actorId = $request->input('actor_id')) {
            $query->where('actor_id', $actorId);
        }

        // Event type filter
        if ($eventType = $request->input('event_type')) {
            $query->where('event_type', $eventType);
        }

        // Target type filter
        if ($targetType = $request->input('target_type')) {
            $query->where('target_type', $targetType);
        }

        // Full-text search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_name', 'ilike', "%{$search}%")
                    ->orWhereRaw("details::text ilike ?", ["%{$search}%"])
                    ->orWhere('event_type', 'ilike', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50)->withQueryString();

        // Get filter options
        $eventTypes = AuditLog::distinct()->pluck('event_type')->sort()->values();
        $targetTypes = AuditLog::distinct()->whereNotNull('target_type')->pluck('target_type')->sort()->values();
        $actors = User::whereIn('id', AuditLog::distinct()->whereNotNull('actor_id')->pluck('actor_id'))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin.audit-logs.index', compact('logs', 'eventTypes', 'targetTypes', 'actors'));
    }

    /**
     * Export filtered audit logs.
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Log the export action
        AuditService::log(
            AuditService::EVENT_AUDIT_LOG_EXPORTED,
            null,
            null,
            [
                'format' => $format,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'filters' => $request->except(['format']),
            ]
        );

        $filename = "AuditLog_{$startDate}_to_{$endDate}_" . now()->format('YmdHis');

        if ($format === 'xlsx') {
            return Excel::download(
                new AuditLogExport($request->all()),
                "{$filename}.xlsx"
            );
        }

        return Excel::download(
            new AuditLogExport($request->all()),
            "{$filename}.csv",
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Apply date preset to query.
     */
    private function applyDatePreset($query, string $preset): void
    {
        $today = now()->toDateString();

        match ($preset) {
            'today' => $query->whereDate('created_at', $today),
            'yesterday' => $query->whereDate('created_at', now()->subDay()->toDateString()),
            'last_7_days' => $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString()),
            'last_30_days' => $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString()),
            'this_month' => $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year),
            'last_month' => $query->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year),
            'this_year' => $query->whereYear('created_at', now()->year),
            default => null,
        };
    }
}
