@extends('layouts.app')

@section('title', 'Booking Details - Admin')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Admin / Bookings /</span> {{ $booking->reference_number }}
            </h4>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to All Bookings
            </a>
        </div>

        <div class="row">
            {{-- Main Details --}}
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Booking Details</h5>
                        {!! $booking->status_badge !!}
                    </div>
                    <div class="card-body">
                        {{-- Booking Info --}}
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Reference Number</label>
                                <p class="fs-5 fw-semibold">{{ $booking->reference_number }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Booking Type</label>
                                <p>
                                    @if ($booking->is_recurring)
                                        <span class="badge bg-info">Recurring</span>
                                        {{ $booking->series->reference_number }}
                                    @else
                                        <span class="badge bg-secondary">One-time</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Date</label>
                                <p class="fs-5">
                                    <i class="bx bx-calendar me-1 text-primary"></i>
                                    {{ $booking->booking_date->format('l, F d, Y') }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Time</label>
                                <p class="fs-5">
                                    <i class="bx bx-time me-1 text-primary"></i>
                                    {{ $booking->time_range }} ({{ $booking->duration }})
                                </p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted">Purpose</label>
                            <p>{{ $booking->purpose }}</p>
                        </div>

                        @if ($booking->status === 'cancelled')
                            <div class="alert alert-danger">
                                <h6 class="alert-heading mb-2">
                                    <i class="bx bx-x-circle me-1"></i> Booking Cancelled
                                </h6>
                                <p class="mb-1"><strong>Reason:</strong> {{ $booking->cancellation_reason }}</p>
                                <p class="mb-0 small">
                                    Cancelled by {{ $booking->cancelledByUser?->name ?? 'Unknown' }}
                                    on {{ $booking->cancelled_at?->format('M d, Y \a\t H:i') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="card-footer">
                        @if ($booking->status !== 'completed')
                            <a href="{{ route('admin.bookings.edit', $booking) }}" class="btn btn-primary me-2">
                                <i class="bx bx-edit me-1"></i> Edit Booking
                            </a>
                        @endif
                        @if ($booking->status === 'confirmed')
                            <button type="button" class="btn btn-outline-danger"
                                onclick="confirmCancel(
                                            {{ $booking->id }}, 
                                            '{{ $booking->reference_number }}',
                                            {{ $booking->is_recurring ? 'true' : 'false' }},
                                            {{ $booking->is_recurring ? $booking->series->bookings()->where('status', 'confirmed')->count() : 0 }},
                                            '{{ $booking->booking_date->format('Y-m-d') }}'
                                        )">
                                <i class="bx bx-x me-1"></i> Cancel Booking
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Series Occurrences --}}
                @if ($booking->is_recurring && $booking->series)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Series Occurrences ({{ $booking->series->bookings->count() }} total)</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->series->bookings()->orderBy('booking_date')->get() as $occurrence)
                                        <tr class="{{ $occurrence->id === $booking->id ? 'table-primary' : '' }}">
                                            <td>{{ $occurrence->reference_number }}</td>
                                            <td>{{ $occurrence->booking_date->format('D, M d, Y') }}</td>
                                            <td>{{ $occurrence->time_range }}</td>
                                            <td>{!! $occurrence->status_badge !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Audit History --}}
                <div class="card mt-4">
                    <div class="card-header border-bottom">
                        <h6 class="mb-0"><i class="bx bx-history me-2"></i>Audit History</h6>
                    </div>
                    @php
                        $auditLogs = \App\Models\AuditLog::where(function ($q) use ($booking) {
                            $q->where('target_type', 'booking')->where('target_id', $booking->id);
                            if ($booking->series_id) {
                                $q->orWhere(function ($sq) use ($booking) {
                                    $sq->where('target_type', 'booking_series')->where(
                                        'target_id',
                                        $booking->series_id,
                                    );
                                });
                            }
                        })
                            ->orderBy('created_at', 'desc')
                            ->limit(20)
                            ->get();
                    @endphp

                    @if ($auditLogs->isEmpty())
                        <div class="card-body text-center py-4">
                            <i class="bx bx-history fs-1 text-muted mb-2"></i>
                            <p class="text-muted mb-0">No audit history available for this booking.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($auditLogs as $log)
                                        <tr>
                                            <td class="small py-2 text-nowrap">{{ $log->created_at->format('M d, Y H:i') }}
                                            </td>
                                            <td class="py-2">
                                                @php
                                                    $isCancelled = \Illuminate\Support\Str::contains(
                                                        $log->event_type,
                                                        'cancelled',
                                                    );
                                                    $isCreated = \Illuminate\Support\Str::contains(
                                                        $log->event_type,
                                                        'created',
                                                    );
                                                @endphp
                                                <span
                                                    class="badge bg-{{ $isCancelled ? 'danger' : ($isCreated ? 'success' : 'primary') }}">
                                                    {{ str_replace('_', ' ', ucfirst($log->event_type)) }}
                                                </span>
                                            </td>
                                            <td class="small py-2">
                                                {{ $log->actor_name ?? 'System' }}
                                            </td>
                                            <td class="small py-2">
                                                @if (is_array($log->details))
                                                    @foreach (array_slice($log->details, 0, 5) as $key => $value)
                                                        @if (!is_array($value))
                                                            <div class="mb-1">
                                                                <span
                                                                    class="text-muted small">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                                                <span
                                                                    class="fw-medium">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-md-4">
                {{-- Booked By --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Booked By</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    {{ strtoupper(substr($booking->user->name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $booking->user->name }}</h6>
                                <small class="text-muted">{{ $booking->user->email }}</small>
                            </div>
                        </div>
                        <hr>
                        <div class="small">
                            <p class="mb-1"><strong>Role:</strong>
                                {{ ucwords(str_replace('_', ' ', $booking->user->role)) }}</p>
                            <p class="mb-0"><strong>Department:</strong> {{ $booking->user->department ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Room Info --}}
                <div class="card mb-4">
                    <img src="{{ $booking->room->primary_image }}" class="card-img-top" alt="{{ $booking->room->name }}"
                        style="height: 150px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">{{ $booking->room->name }}</h5>
                        <p class="card-text">
                            <i class="bx bx-user me-1"></i> {{ $booking->room->capacity }} seats<br>
                            <i class="bx bx-map me-1"></i> {{ $booking->room->floor_location }}
                        </p>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Activity Timeline</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Created</span>
                            <span>{{ $booking->created_at->format('M d, Y H:i') }}</span>
                        </li>
                        @if ($booking->updated_at != $booking->created_at && $booking->status !== 'cancelled')
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Last Updated</span>
                                <span>{{ $booking->updated_at->format('M d, Y H:i') }}</span>
                            </li>
                        @endif
                        @if ($booking->cancelled_at)
                            <li class="list-group-item d-flex justify-content-between text-danger">
                                <span>Cancelled</span>
                                <span>{{ $booking->cancelled_at->format('M d, Y H:i') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Modal --}}
    @include('admin.bookings.partials.cancel-modal')
    @include('admin.bookings.partials.cancel-recurring-modal')

    @push('scripts')
        <script>
            function confirmCancel(bookingId, reference, isRecurring, seriesCount, date) {
                if (isRecurring) {
                    confirmCancelRecurring(bookingId, reference, date, `{{ url('admin/bookings') }}/${bookingId}`);
                } else {
                    document.getElementById('cancelRef').textContent = reference;
                    document.getElementById('cancelForm').action = `{{ url('admin/bookings') }}/${bookingId}`;

                    const seriesWarning = document.getElementById('seriesWarning');
                    if (seriesWarning) {
                        seriesWarning.style.display = 'none';
                    }

                    new bootstrap.Modal(document.getElementById('cancelModal')).show();
                }
            }
        </script>
    @endpush
@endsection
