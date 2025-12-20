@extends('layouts.app')

@section('title', 'Edit Booking')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">My Bookings /</span> Edit {{ $booking->reference_number }}
        </h4>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Booking</h5>
                    {!! $booking->status_badge !!}
                </div>
                <div class="card-body">
                    @if($isSeriesEdit)
                        <div class="alert alert-warning mb-4">
                            <i class="bx bx-repeat me-1"></i>
                            <strong>Recurring Booking:</strong> This is part of a series ({{ $booking->series->reference_number }}).
                            Changes will apply to the <strong>entire series</strong> ({{ $booking->series->bookings()->where('status', 'confirmed')->count() }} bookings).
                        </div>
                    @endif

                    <form action="{{ route('my-bookings.update', $booking) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if($isSeriesEdit)
                            <input type="hidden" name="update_series" value="1">
                        @endif

                        {{-- Original Values (for comparison) --}}
                        <div class="row mb-4 p-3 bg-light rounded">
                            <div class="col-12">
                                <h6 class="text-muted mb-2">Current Booking</h6>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Room</small>
                                <p class="mb-0">{{ $booking->room->name }}</p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Date</small>
                                <p class="mb-0">{{ $booking->booking_date->format('M d, Y') }}</p>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Time</small>
                                <p class="mb-0">{{ $booking->time_range }}</p>
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3">New Values</h6>

                        {{-- Room Selection --}}
                        <div class="mb-3">
                            <label class="form-label" for="room_id">Meeting Room</label>
                            <select class="form-select @error('room_id') is-invalid @enderror" 
                                    id="room_id" name="room_id" required>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" 
                                            {{ old('room_id', $booking->room_id) == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }} ({{ $room->capacity }} seats, {{ $room->floor_location }})
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Date (only for single bookings) --}}
                        @if(!$isSeriesEdit)
                            <div class="mb-3">
                                <label class="form-label" for="booking_date">Date</label>
                                <input type="date" 
                                       class="form-control @error('booking_date') is-invalid @enderror" 
                                       id="booking_date" 
                                       name="booking_date" 
                                       min="{{ date('Y-m-d') }}"
                                       value="{{ old('booking_date', $booking->booking_date->format('Y-m-d')) }}"
                                       required>
                                @error('booking_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @else
                            <input type="hidden" name="booking_date" value="{{ $booking->booking_date->format('Y-m-d') }}">
                            <div class="alert alert-info mb-3">
                                <i class="bx bx-info-circle me-1"></i>
                                Series booking dates cannot be changed. To change dates, cancel and create a new series.
                            </div>
                        @endif

                        {{-- Time Selection --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="start_time">Start Time</label>
                                <select class="form-select @error('start_time') is-invalid @enderror" 
                                        id="start_time" name="start_time" required>
                                    @for($hour = 8; $hour < 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @php 
                                                $time = sprintf('%02d:%s', $hour, $minute);
                                                $currentStart = Carbon\Carbon::parse($booking->start_time)->format('H:i');
                                            @endphp
                                            <option value="{{ $time }}" 
                                                    {{ old('start_time', $currentStart) == $time ? 'selected' : '' }}>
                                                {{ $time }}
                                            </option>
                                        @endforeach
                                    @endfor
                                </select>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="end_time">End Time</label>
                                <select class="form-select @error('end_time') is-invalid @enderror" 
                                        id="end_time" name="end_time" required>
                                    @for($hour = 8; $hour <= 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @if($hour == 8 && $minute == '00') @continue @endif
                                            @php 
                                                $time = sprintf('%02d:%s', $hour, $minute);
                                                $currentEnd = Carbon\Carbon::parse($booking->end_time)->format('H:i');
                                            @endphp
                                            <option value="{{ $time }}"
                                                    {{ old('end_time', $currentEnd) == $time ? 'selected' : '' }}>
                                                {{ $time }}
                                            </option>
                                        @endforeach
                                    @endfor
                                </select>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Availability Status --}}
                        <div id="availabilityStatus" class="mb-3" style="display: none;">
                            <div id="availabilityMessage" class="alert"></div>
                        </div>

                        {{-- Purpose --}}
                        <div class="mb-3">
                            <label class="form-label" for="purpose">Purpose of Booking</label>
                            <textarea class="form-control @error('purpose') is-invalid @enderror" 
                                      id="purpose" 
                                      name="purpose" 
                                      rows="3" 
                                      maxlength="500"
                                      required>{{ old('purpose', $booking->purpose) }}</textarea>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Read-only fields --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Reference Number</label>
                                <p class="form-control-plaintext">{{ $booking->reference_number }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Booked By</label>
                                <p class="form-control-plaintext">{{ $booking->user->name }}</p>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                <i class="bx bx-save me-1"></i> Save Changes
                            </button>
                            <a href="{{ route('my-bookings.show', $booking) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info Sidebar --}}
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Edit Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bx bx-check text-success me-1"></i>
                            Status remains <strong>{{ ucfirst($booking->status) }}</strong> after edit
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-check text-success me-1"></i>
                            Reference number is preserved
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-check text-success me-1"></i>
                            Changes are logged in audit trail
                        </li>
                        @if($isSeriesEdit)
                            <li class="mb-2">
                                <i class="bx bx-error text-warning me-1"></i>
                                All {{ $booking->series->bookings()->where('status', 'confirmed')->count() }} occurrences will be updated
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_id');
    const dateInput = document.getElementById('booking_date');
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    const submitBtn = document.getElementById('submitBtn');
    
    let availabilityCheckTimeout;

    // Trigger availability check on change
    [roomSelect, dateInput, startTimeSelect, endTimeSelect].forEach(el => {
        if (el) el.addEventListener('change', checkAvailability);
    });

    function checkAvailability() {
        clearTimeout(availabilityCheckTimeout);
        
        const roomId = roomSelect.value;
        const date = dateInput ? dateInput.value : '{{ $booking->booking_date->format("Y-m-d") }}';
        const startTime = startTimeSelect.value;
        const endTime = endTimeSelect.value;

        if (!roomId || !date || !startTime || !endTime) {
            return;
        }

        availabilityCheckTimeout = setTimeout(() => {
            fetch('{{ route("ajax.bookings.check-availability") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    room_id: roomId,
                    booking_date: date,
                    start_time: startTime,
                    end_time: endTime,
                    exclude_booking_id: {{ $booking->id }},
                }),
            })
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('availabilityStatus');
                const message = document.getElementById('availabilityMessage');
                
                container.style.display = 'block';
                
                if (data.available) {
                    message.className = 'alert alert-success';
                    message.innerHTML = '<i class="bx bx-check-circle me-1"></i> Time slot is available!';
                    submitBtn.disabled = false;
                } else {
                    message.className = 'alert alert-danger';
                    message.innerHTML = `<i class="bx bx-x-circle me-1"></i> ${data.conflict?.message || 'Time slot is not available'}`;
                    submitBtn.disabled = true;
                }
            });
        }, 500);
    }
});
</script>
@endpush
@endsection
