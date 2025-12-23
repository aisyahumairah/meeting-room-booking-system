@extends('layouts.app')

@section('title', 'My Bookings')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Bookings /</span> My Bookings
            </h4>
            <div>
                <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> New Booking
                </a>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-calendar"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Total Bookings</small>
                                <h5 class="mb-0">{{ $stats['total'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-check-circle"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Confirmed</small>
                                <h5 class="mb-0">{{ $stats['confirmed'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-secondary">
                                    <i class="bx bx-check-double"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Completed</small>
                                <h5 class="mb-0">{{ $stats['completed'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="bx bx-x-circle"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Cancelled</small>
                                <h5 class="mb-0">{{ $stats['cancelled'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- View Toggle & Filters --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                {{-- View Toggle --}}
                <ul class="nav nav-pills" id="viewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView"
                            type="button" role="tab">
                            <i class="bx bx-list-ul me-1"></i> List
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendarView"
                            type="button" role="tab">
                            <i class="bx bx-calendar me-1"></i> Calendar
                        </button>
                    </li>
                </ul>

                {{-- Quick Filters --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('my-bookings', ['filter' => 'upcoming']) }}"
                        class="btn btn-outline-primary btn-sm {{ request('filter') === 'upcoming' ? 'active' : '' }}">
                        Upcoming
                    </a>
                    <a href="{{ route('my-bookings', ['filter' => 'past']) }}"
                        class="btn btn-outline-secondary btn-sm {{ request('filter') === 'past' ? 'active' : '' }}">
                        Past
                    </a>
                    <a href="{{ route('my-bookings') }}"
                        class="btn btn-outline-dark btn-sm {{ !request('filter') ? 'active' : '' }}">
                        All
                    </a>
                </div>
            </div>

            {{-- Advanced Filters --}}
            <div class="card-body border-bottom">
                <form action="{{ route('my-bookings') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Statuses</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed
                            </option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled
                            </option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Room</label>
                        <select class="form-select form-select-sm" name="room_id">
                            <option value="">All Rooms</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}"
                                    {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                    {{ $room->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control form-control-sm" name="date_from"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control form-control-sm" name="date_to"
                            value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ route('my-bookings') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    </div>
                </form>
            </div>

            {{-- Tab Content --}}
            <div class="tab-content">
                {{-- List View --}}
                <div class="tab-pane fade show active" id="listView" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Date & Time</th>
                                    <th>Room</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                    <tr>
                                        <td>
                                            <a href="{{ route('my-bookings.show', $booking) }}">
                                                {{ $booking->reference_number }}
                                            </a>
                                            @if ($booking->is_recurring)
                                                <i class="bx bx-repeat text-info" title="Recurring"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $booking->booking_date->format('D, M d, Y') }}</div>
                                            <small class="text-muted">{{ $booking->time_range }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $booking->room->name }}</div>
                                            <small class="text-muted">{{ $booking->room->floor_location }}</small>
                                        </td>
                                        <td>
                                            <span title="{{ $booking->purpose }}">
                                                {{ Str::limit($booking->purpose, 40) }}
                                            </span>
                                        </td>
                                        <td>{!! $booking->status_badge !!}</td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item"
                                                        href="{{ route('my-bookings.show', $booking) }}">
                                                        <i class="bx bx-show me-1"></i> View Details
                                                    </a>
                                                    @if ($booking->is_editable)
                                                        <a class="dropdown-item"
                                                            href="{{ route('my-bookings.edit', $booking) }}">
                                                            <i class="bx bx-edit me-1"></i> Edit
                                                        </a>
                                                    @endif
                                                    @if ($booking->is_cancellable)
                                                        <a class="dropdown-item text-danger" href="#"
                                                            onclick="confirmCancel(
                                                                            {{ $booking->id }},
                                                                            '{{ $booking->reference_number }}',
                                                                            {{ $booking->is_recurring ? 'true' : 'false' }},
                                                                            {{ $booking->is_recurring ? $booking->series->bookings()->where('status', 'confirmed')->count() : 0 }}
                                                                        )">
                                                            <i class="bx bx-x me-1"></i> Cancel
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bx bx-calendar-x bx-lg mb-2"></i>
                                                <p class="mb-2">No bookings found</p>
                                                <a href="{{ route('bookings.create') }}" class="btn btn-primary btn-sm">
                                                    Create Your First Booking
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($bookings->hasPages())
                        <div class="card-footer">
                            {{ $bookings->links() }}
                        </div>
                    @endif
                </div>

                {{-- Calendar View --}}
                <div class="tab-pane fade" id="calendarView" role="tabpanel">
                    <div class="card-body">
                        <div id="myBookingsCalendar" style="min-height: 600px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('bookings.partials.cancel-modal')

    {{-- Booking Details Modal --}}
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8" id="modalReference"></dd>

                        <dt class="col-sm-4">Room</dt>
                        <dd class="col-sm-8" id="modalRoom"></dd>

                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8" id="modalDate"></dd>

                        <dt class="col-sm-4">Time</dt>
                        <dd class="col-sm-8" id="modalTime"></dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8" id="modalStatus"></dd>

                        <dt class="col-sm-4">Purpose</dt>
                        <dd class="col-sm-8 text-wrap" id="modalPurpose"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <a href="#" id="modalViewLink" class="btn btn-primary btn-sm">View Full Details</a>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        {{-- FullCalendar v6 injects CSS automatically via JS, so no external CSS file is needed --}}
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let calendar;

                // Initialize calendar when tab is shown
                document.getElementById('calendar-tab').addEventListener('shown.bs.tab', function() {
                    if (!calendar) {
                        initCalendar();
                    } else {
                        calendar.updateSize();
                    }
                });

                function initCalendar() {
                    const calendarEl = document.getElementById('myBookingsCalendar');
                    calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        slotMinTime: '08:00:00',
                        slotMaxTime: '18:00:00',
                        weekends: false,
                        height: 'auto',
                        events: function(info, successCallback, failureCallback) {
                            fetch(
                                    `{{ route('ajax.my-bookings.calendar') }}?start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(info.endStr)}`
                                )
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    // console.log('Events fetched:', data);
                                    successCallback(data);
                                })
                                .catch(error => {
                                    console.error('Error fetching events:', error);
                                    failureCallback(error);
                                });
                        },
                        eventClick: function(info) {
                            info.jsEvent.preventDefault();

                            // Populate modal data
                            const props = info.event.extendedProps;
                            const start = info.event.start;
                            const end = info.event.end;

                            if (!props) return;

                            document.getElementById('modalReference').textContent = props.reference ||
                                '-';
                            document.getElementById('modalRoom').textContent = props.room || '-';

                            // Format Date
                            const dateOptions = {
                                weekday: 'short',
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            };
                            document.getElementById('modalDate').textContent = start ? start
                                .toLocaleDateString('en-US', dateOptions) : '-';

                            // Format Time
                            const timeOptions = {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            };
                            const startTime = start ? start.toLocaleTimeString('en-US',
                                timeOptions) : '';
                            const endTime = end ? end.toLocaleTimeString('en-US', timeOptions) : '';
                            document.getElementById('modalTime').textContent =
                                `${startTime} - ${endTime}`;

                            // Status badge
                            const statusEl = document.getElementById('modalStatus');
                            statusEl.innerHTML =
                                `<span class="badge" style="background-color: ${info.event.backgroundColor}">${props.status.toUpperCase()}</span>`;

                            document.getElementById('modalPurpose').textContent = props.purpose ||
                                '-';

                            // View Link
                            const viewLink = document.getElementById('modalViewLink');
                            if (info.event.url) {
                                viewLink.href = info.event.url;
                                viewLink.hidden = false;
                            } else {
                                viewLink.hidden = true;
                            }

                            // Show modal
                            const modal = new bootstrap.Modal(document.getElementById(
                                'bookingDetailsModal'));
                            modal.show();
                        },
                        eventDidMount: function(info) {
                            // Add tooltip
                            if (info.event.extendedProps.room) {
                                info.el.title =
                                    `${info.event.extendedProps.room}\n${info.event.extendedProps.purpose || ''}`;
                            }
                        }
                    });
                    calendar.render();
                }
            });
        </script>
    @endpush
@endsection
