@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">My Bookings /</span> {{ $booking->reference_number }}
            </h4>
            <a href="{{ route('my-bookings') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Back to My Bookings
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
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Reference Number</label>
                                <p class="fs-5 fw-semibold">{{ $booking->reference_number }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Status</label>
                                <p>{!! $booking->status_badge !!}</p>
                            </div>
                        </div>

                        <div class="row mb-3">
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

                        <div class="mb-3">
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

                        @if ($booking->is_recurring)
                            <div class="alert alert-info">
                                <i class="bx bx-repeat me-1"></i>
                                <strong>Recurring Booking</strong> - Part of series
                                {{ $booking->series->reference_number }}
                                ({{ $booking->series->occurrence_count }} occurrences)
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="card-footer">
                        @if ($booking->is_editable)
                            <a href="{{ route('my-bookings.edit', $booking) }}" class="btn btn-primary me-2">
                                <i class="bx bx-edit me-1"></i> Edit Booking
                            </a>
                        @endif
                        @if ($booking->is_cancellable)
                            <button type="button" class="btn btn-outline-danger"
                                onclick="handleCancelClick(
                                        {{ $booking->id }},
                                        '{{ $booking->reference_number }}',
                                        {{ $booking->is_recurring ? 'true' : 'false' }},
                                        '{{ $booking->booking_date->format('M d, Y') }}'
                                    )">
                                <i class="bx bx-x me-1"></i> Cancel Booking
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Series Occurrences (if recurring) --}}
                @if ($booking->is_recurring && $booking->series)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Series Occurrences</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->series->bookings()->orderBy('booking_date')->get() as $occurrence)
                                        <tr class="{{ $occurrence->id === $booking->id ? 'table-primary' : '' }}">
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

                {{-- Audit History (Admin only) --}}
                @if (auth()->user()->canManageBookings())
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
                                                <td class="small py-2 text-nowrap">
                                                    {{ $log->created_at->format('M d, Y H:i') }}</td>
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
                                                <td class="small py-2">{{ $log->actor_name ?? 'System' }}</td>
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
                @endif
            </div>

            {{-- Room Info Sidebar --}}
            <div class="col-md-4">
                <div class="card mb-4">
                    <img src="{{ $booking->room->primary_image }}" class="card-img-top" alt="{{ $booking->room->name }}"
                        style="height: 150px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title">{{ $booking->room->name }}</h5>
                        <p class="card-text">
                            <i class="bx bx-user me-1"></i> {{ $booking->room->capacity }} seats<br>
                            <i class="bx bx-map me-1"></i> {{ $booking->room->floor_location }}
                        </p>
                        <a href="{{ route('rooms.show', $booking->room) }}" class="btn btn-outline-primary btn-sm">
                            View Room Details
                        </a>
                    </div>
                </div>

                {{-- Booking Info --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Booking Information</h6>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Booked by</span>
                            <span>{{ $booking->user->name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Created</span>
                            <span>{{ $booking->created_at->format('M d, Y H:i') }}</span>
                        </li>
                        @if ($booking->updated_at != $booking->created_at)
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Last Updated</span>
                                <span>{{ $booking->updated_at->format('M d, Y H:i') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Modal (same as in my.blade.php) --}}
    @include('bookings.partials.cancel-modal')
    @include('bookings.partials.cancel-recurring-modal')

    @push('scripts')
        <script>
            function confirmCancel(bookingId, reference) {
                document.getElementById('cancelRef').textContent = reference;
                document.getElementById('cancelForm').action = `/my-bookings/${bookingId}`;
                new bootstrap.Modal(document.getElementById('cancelModal')).show();
            }

            function confirmCancelRecurring(bookingId, reference, date) {
                document.getElementById('cancelRecurringRef').textContent = reference;
                document.getElementById('cancelDate').textContent = date;
                document.getElementById('cancelRecurringForm').action = `/my-bookings/${bookingId}`;
                new bootstrap.Modal(document.getElementById('cancelRecurringModal')).show();
            }

            function handleCancelClick(bookingId, reference, isRecurring, dateString) {
                if (isRecurring) {
                    confirmCancelRecurring(bookingId, reference, dateString);
                } else {
                    confirmCancel(bookingId, reference);
                }
            }
        </script>
    @endpush
@endsection
