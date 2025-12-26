@extends('layouts.app')

@section('title', $room->name)

@section('content')
    <div class="row">
        {{-- Back Button & Header --}}
        <div class="col-12 mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('rooms.index') }}" class="btn btn-icon btn-outline-secondary me-3">
                    <i class="bx bx-arrow-back"></i>
                </a>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="mb-0">{{ $room->name }}</h4>
                        {!! $room->status_badge !!}
                    </div>
                    <p class="text-muted mb-0">
                        <i class="bx bx-user"></i> {{ $room->capacity }} people
                        <span class="mx-2">|</span>
                        <i class="bx bx-map"></i> {{ $room->floor_location }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Left Column: Gallery & Description --}}
        <div class="col-lg-8">
            {{-- Photo Gallery --}}
            <div class="card mb-4">
                <div class="card-body">
                    @if ($room->images->count() > 0)
                        {{-- Main Image --}}
                        <div class="room-gallery-main mb-3">
                            <img src="{{ $room->primary_image }}" alt="{{ $room->name }}" class="img-fluid rounded w-100"
                                id="mainImage" style="max-height: 400px; object-fit: cover; cursor: pointer;"
                                data-bs-toggle="modal" data-bs-target="#imageModal">
                        </div>

                        {{-- Thumbnail Gallery --}}
                        @if ($room->images->count() > 1)
                            <div class="room-gallery-thumbs d-flex gap-2 overflow-auto">
                                @foreach ($room->images as $image)
                                    <img src="{{ $image->url }}" alt="Room Image"
                                        class="room-thumb rounded {{ $image->is_primary ? 'active' : '' }}"
                                        style="width: 80px; height: 60px; object-fit: cover; cursor: pointer;"
                                        onclick="changeMainImage('{{ $image->url }}', this)">
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5 bg-light rounded">
                            <i class="bx bx-image bx-lg text-muted"></i>
                            <p class="text-muted mb-0 mt-2">No images available</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Description --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">About This Room</h5>
                </div>
                <div class="card-body">
                    @if ($room->description)
                        <p class="mb-0">{{ $room->description }}</p>
                    @else
                        <p class="text-muted mb-0">No description available.</p>
                    @endif
                </div>
            </div>

            {{-- Availability Calendar --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Availability</h5>
                    <div class="d-flex gap-2">
                        <span class="badge bg-label-primary d-flex align-items-center">
                            <span class="bg-primary rounded-circle me-1" style="width: 8px; height: 8px;"></span>
                            Booked
                        </span>
                        <span class="badge bg-label-success d-flex align-items-center">
                            <span class="bg-success rounded-circle me-1" style="width: 8px; height: 8px;"></span>
                            Your Booking
                        </span>
                        <span class="badge bg-label-secondary d-flex align-items-center">
                            <span class="bg-secondary rounded-circle me-1" style="width: 8px; height: 8px;"></span>
                            Completed
                        </span>
                        <span class="badge bg-label-danger d-flex align-items-center">
                            <span class="bg-danger rounded-circle me-1" style="width: 8px; height: 8px;"></span>
                            Maintenance
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div id="availability-calendar"></div>
                </div>
            </div>
        </div>

        {{-- Right Column: Specs & Actions --}}
        <div class="col-lg-4">
            {{-- Room Specifications --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Room Specifications</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-user"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Capacity</small>
                                <span class="fw-semibold">{{ $room->capacity }} people</span>
                            </div>
                        </li>
                        <li class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-map"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Location</small>
                                <span class="fw-semibold">{{ $room->floor_location }}</span>
                            </div>
                        </li>
                        <li class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3">
                                <span
                                    class="avatar-initial rounded bg-label-{{ $room->status === 'active' ? 'success' : ($room->status === 'under_maintenance' ? 'warning' : 'secondary') }}">
                                    <i
                                        class="bx bx-{{ $room->status === 'active' ? 'check-circle' : ($room->status === 'under_maintenance' ? 'wrench' : 'x-circle') }}"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Status</small>
                                {!! $room->status_badge !!}
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Amenities --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Amenities</h5>
                </div>
                <div class="card-body">
                    @if ($room->amenities->count() > 0)
                        <div class="row g-3">
                            @foreach ($room->amenities as $amenity)
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 text-primary">{!! $amenity->icon_html !!}</span>
                                        <span>{{ $amenity->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No amenities listed</p>
                    @endif
                </div>
            </div>

            {{-- Book Button --}}
            <div class="card">
                <div class="card-body">
                    @if ($room->status === 'under_maintenance')
                        <button class="btn btn-secondary w-100" disabled>
                            <i class="bx bx-wrench me-1"></i> Under Maintenance
                        </button>
                        <p class="text-muted small text-center mt-2 mb-0">
                            This room is currently unavailable for booking.
                        </p>
                    @elseif($room->status === 'inactive')
                        <button class="btn btn-secondary w-100" disabled>
                            <i class="bx bx-x-circle me-1"></i> Room Unavailable
                        </button>
                    @else
                        <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" class="btn btn-primary w-100">
                            <i class="bx bx-calendar-plus me-1"></i> Book This Room
                        </a>
                        <p class="text-muted small text-center mt-2 mb-0">
                            Select date and time on the calendar above
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Image Lightbox Modal --}}
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body p-0 text-center">
                    <img src="{{ $room->primary_image }}" alt="{{ $room->name }}" class="img-fluid rounded"
                        id="modalImage" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>

    {{-- Booking Details Modal --}}
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0 mt-2">
                        <dt class="col-sm-4 mb-2">Reference</dt>
                        <dd class="col-sm-8 mb-2"><span id="modalReference" class="fw-bold text-primary"></span></dd>

                        <dt class="col-sm-4 mb-2">Date</dt>
                        <dd class="col-sm-8 mb-2" id="modalDate"></dd>

                        <dt class="col-sm-4 mb-2">Time</dt>
                        <dd class="col-sm-8 mb-2" id="modalTime"></dd>

                        <dt class="col-sm-4 mb-2">Booker</dt>
                        <dd class="col-sm-8 mb-2" id="modalBooker"></dd>

                        <dt class="col-sm-4 mb-2">Status</dt>
                        <dd class="col-sm-8 mb-2" id="modalStatus"></dd>

                        <dt class="col-sm-4 mb-0">Purpose</dt>
                        <dd class="col-sm-8 mb-0" id="modalPurpose"></dd>
                    </dl>
                </div>
                <div class="modal-footer mt-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="{{ asset('assets/css/rooms.css') }}">
    <style>
        .room-thumb.active {
            border: 2px solid #696cff;
        }

        .room-thumb:hover {
            opacity: 0.8;
        }
    </style>
@endpush

@push('scripts')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script>
        // Change main image on thumbnail click
        function changeMainImage(url, thumb) {
            document.getElementById('mainImage').src = url;
            document.getElementById('modalImage').src = url;

            // Update active state
            document.querySelectorAll('.room-thumb').forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
        }

        // Initialize FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('availability-calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridDay,timeGridWeek'
                },
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                weekends: false,
                allDaySlot: false,
                slotDuration: '00:30:00',
                height: 'auto',
                events: '{{ route('api.rooms.availability', $room) }}',
                eventColor: '#696cff',
                nowIndicator: true,
                selectable: true,
                selectMirror: true,

                // Prevent selecting past dates
                selectAllow: function(selectInfo) {
                    const now = new Date();
                    now.setHours(0, 0, 0, 0);
                    return selectInfo.start >= now;
                },

                // Handle selection for quick booking
                select: function(info) {
                    const now = new Date();
                    now.setHours(0, 0, 0, 0);

                    if (info.start < now) {
                        calendar.unselect();
                        return;
                    }

                    const date = info.startStr.split('T')[0];
                    const startTime = info.startStr.includes('T') ?
                        info.startStr.split('T')[1].substring(0, 5) :
                        '09:00';
                    const endTime = info.endStr.includes('T') ?
                        info.endStr.split('T')[1].substring(0, 5) :
                        '10:00';

                    // Redirect to create booking with pre-filled values
                    window.location.href =
                        `{{ route('bookings.create') }}?room_id={{ $room->id }}&date=${date}&start_time=${startTime}&end_time=${endTime}`;
                },
                eventClick: function(info) {
                    // Handle maintenance or background events (no reference)
                    if (!info.event.extendedProps.reference) return;

                    const props = info.event.extendedProps;
                    const start = info.event.start;
                    const end = info.event.end;

                    document.getElementById('modalReference').textContent = props.reference;

                    // Format Date
                    const dateOptions = {
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    };
                    document.getElementById('modalDate').textContent = start.toLocaleDateString('en-US',
                        dateOptions);

                    // Format Time
                    const timeOptions = {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    };
                    const startTime = start.toLocaleTimeString('en-US', timeOptions);
                    const endTime = end.toLocaleTimeString('en-US', timeOptions);
                    document.getElementById('modalTime').textContent = `${startTime} - ${endTime}`;

                    document.getElementById('modalBooker').textContent = props.booker;

                    const statusBadgeMap = {
                        confirmed: '<span class="badge bg-success">Confirmed</span>',
                        completed: '<span class="badge bg-secondary">Completed</span>',
                        cancelled: '<span class="badge bg-danger">Cancelled</span>'
                    };
                    document.getElementById('modalStatus').innerHTML = statusBadgeMap[props.status] ||
                        props.status;

                    document.getElementById('modalPurpose').textContent = info.event.title;

                    const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
                    modal.show();
                },
                eventDidMount: function(info) {
                    // Add tooltip
                    const props = info.event.extendedProps;
                    if (props.reference) {
                        let tooltipText = `Ref: ${props.reference} (${props.status})`;
                        if (props.booker) {
                            tooltipText += `\nBy: ${props.booker}`;
                        }
                        info.el.title = tooltipText;
                    } else if (info.event.title) {
                        info.el.title = info.event.title;
                    }
                }
            });
            calendar.render();
        });
    </script>
@endpush
