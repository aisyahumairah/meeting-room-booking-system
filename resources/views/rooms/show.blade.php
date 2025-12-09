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
                        <a href="#" class="btn btn-primary w-100">
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
                selectable: false,
                eventClick: function(info) {
                    // Show booking details in tooltip
                    if (info.event.extendedProps.reference) {
                        alert('Booking: ' + info.event.extendedProps.reference + '\n' + info.event
                            .title);
                    }
                },
                eventDidMount: function(info) {
                    // Add tooltip
                    if (info.event.extendedProps.booker) {
                        info.el.title = 'Booked by: ' + info.event.extendedProps.booker;
                    }
                }
            });
            calendar.render();
        });
    </script>
@endpush
