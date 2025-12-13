@extends('layouts.app')

@section('title', 'Booking Details - ' . $booking->reference_number)

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class='bx bx-calendar-check me-2'></i>Booking Details
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Booking {{ $booking->reference_number }}</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class='bx bx-arrow-back me-1'></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Booking Information -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $booking->reference_number }}</h5>
                        {!! $booking->status_badge !!}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Room</label>
                                <p class="mb-0 fs-5">
                                    <i class='bx bx-door-open text-primary me-1'></i>
                                    {{ $booking->room->name }}
                                </p>
                                <small class="text-muted">{{ $booking->room->location }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Date</label>
                                <p class="mb-0 fs-5">
                                    <i class='bx bx-calendar text-primary me-1'></i>
                                    {{ $booking->booking_date->format('l, F j, Y') }}
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Time</label>
                                <p class="mb-0 fs-5">
                                    <i class='bx bx-time text-primary me-1'></i>
                                    {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} -
                                    {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
                                </p>
                                <small class="text-muted">Duration: {{ $booking->duration }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-semibold">Booked By</label>
                                <p class="mb-0 fs-5">
                                    <i class='bx bx-user text-primary me-1'></i>
                                    {{ $booking->user->name }}
                                </p>
                                <small class="text-muted">{{ $booking->user->department ?? 'No department' }}</small>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Purpose</label>
                            <p class="mb-0">{{ $booking->purpose }}</p>
                        </div>

                        @if ($booking->attendees)
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Attendees</label>
                                <p class="mb-0">{{ $booking->attendees }}</p>
                            </div>
                        @endif

                        @if ($booking->notes)
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Notes</label>
                                <p class="mb-0">{{ $booking->notes }}</p>
                            </div>
                        @endif

                        @if ($booking->is_recurring && $booking->series)
                            <div class="alert alert-info mb-0">
                                <i class='bx bx-repeat me-1'></i>
                                This is a recurring booking ({{ ucfirst($booking->series->recurrence_type) }}).
                            </div>
                        @endif

                        @if ($booking->status === 'cancelled')
                            <div class="alert alert-danger mb-0">
                                <strong><i class='bx bx-x-circle me-1'></i>This booking was cancelled.</strong>
                                @if ($booking->cancellation_reason)
                                    <p class="mb-0 mt-2">Reason: {{ $booking->cancellation_reason }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Room Information -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Room Details</h6>
                    </div>
                    @if ($booking->room->primaryImage)
                        <img src="{{ $booking->room->primaryImage->url }}" class="card-img-top"
                            alt="{{ $booking->room->name }}" style="height: 150px; object-fit: cover;">
                    @endif
                    <div class="card-body">
                        <h5 class="card-title">{{ $booking->room->name }}</h5>
                        <p class="text-muted mb-2">
                            <i class='bx bx-map-pin me-1'></i>{{ $booking->room->location }}
                        </p>
                        <p class="text-muted mb-2">
                            <i class='bx bx-group me-1'></i>Capacity: {{ $booking->room->capacity }} people
                        </p>
                        @if ($booking->room->amenities->isNotEmpty())
                            <div class="mt-3">
                                <small class="text-muted fw-semibold d-block mb-2">Amenities:</small>
                                @foreach ($booking->room->amenities as $amenity)
                                    <span class="badge bg-label-primary me-1 mb-1">
                                        <i class='bx {{ $amenity->icon }} me-1'></i>{{ $amenity->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Actions Card -->
                @if ($booking->is_cancellable && $booking->user_id === auth()->id())
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <form action="#" method="POST"
                                onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100" disabled>
                                    <i class='bx bx-x-circle me-1'></i>Cancel Booking
                                </button>
                                <small class="text-muted d-block mt-2 text-center">
                                    Cancellation feature coming soon
                                </small>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
