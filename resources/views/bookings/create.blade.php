@extends('layouts.app')

@section('title', 'Book a Room')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Bookings /</span> Book a Room
        </h4>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">New Booking</h5>
                        <small class="text-muted float-end">All fields are required</small>
                    </div>
                    <div class="card-body">
                        <form id="bookingForm" action="{{ route('bookings.store') }}" method="POST">
                            @csrf

                            {{-- Race condition warning --}}
                            @if (session('error'))
                                <div class="alert alert-danger alert-dismissible mb-4">
                                    <i class="bx bx-error-circle me-2"></i>
                                    <strong>Booking Failed!</strong>
                                    <p class="mb-0 mt-2">{{ session('error') }}</p>
                                    <p class="mb-0 mt-2 small">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Tip: The selection below has been preserved. Please choose a different time and try
                                        again.
                                    </p>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            {{-- Room Selection --}}
                            <div class="mb-3">
                                <label class="form-label" for="room_id">Meeting Room</label>
                                <select class="form-select @error('room_id') is-invalid @enderror" id="room_id"
                                    name="room_id" required>
                                    <option value="">Select a room...</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}" data-capacity="{{ $room->capacity }}"
                                            data-location="{{ $room->floor_location }}"
                                            data-image="{{ $room->primary_image }}"
                                            {{ old('room_id', $selectedRoom?->id) == $room->id ? 'selected' : '' }}>
                                            {{ $room->name }} ({{ $room->capacity }} seats, {{ $room->floor_location }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Booking Date --}}
                            <div class="mb-3">
                                <label class="form-label" for="booking_date">Date</label>
                                <input type="date" class="form-control @error('booking_date') is-invalid @enderror"
                                    id="booking_date" name="booking_date"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                                    value="{{ old('booking_date', $prefilledDate ?? '') }}" required
                                    onkeydown="return false;"
                                    onchange="if(this.value < this.min) { alert('Past dates are not allowed.'); this.value=''; }">
                                @error('booking_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Time Selection --}}
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="start_time">Start Time</label>
                                    <select class="form-select @error('start_time') is-invalid @enderror" id="start_time"
                                        name="start_time" required>
                                        <option value="">Select start time...</option>
                                        @for ($hour = 8; $hour < 18; $hour++)
                                            @foreach (['00', '30'] as $minute)
                                                @php $time = sprintf('%02d:%s', $hour, $minute); @endphp
                                                <option value="{{ $time }}"
                                                    {{ old('start_time', $prefilledStartTime ?? '') == $time ? 'selected' : '' }}>
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
                                    <select class="form-select @error('end_time') is-invalid @enderror" id="end_time"
                                        name="end_time" required>
                                        <option value="">Select end time...</option>
                                        @for ($hour = 8; $hour <= 18; $hour++)
                                            @foreach (['00', '30'] as $minute)
                                                @if ($hour == 8 && $minute == '00')
                                                    @continue
                                                @endif
                                                @php $time = sprintf('%02d:%s', $hour, $minute); @endphp
                                                <option value="{{ $time }}"
                                                    {{ old('end_time', $prefilledEndTime ?? '') == $time ? 'selected' : '' }}>
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

                            {{-- Duration Display --}}
                            <div class="mb-3">
                                <small class="text-muted">
                                    Duration: <span id="durationDisplay">--</span>
                                    <span class="text-info">(Min: 30 min, Max: 8 hours)</span>
                                </small>
                            </div>

                            {{-- Availability Status --}}
                            <div id="availabilityStatus" class="mb-3" style="display: none;">
                                <div id="availabilityMessage" class="alert"></div>
                            </div>

                            {{-- Purpose --}}
                            <div class="mb-3">
                                <label class="form-label" for="purpose">Purpose of Booking</label>
                                <textarea class="form-control @error('purpose') is-invalid @enderror" id="purpose" name="purpose" rows="3"
                                    maxlength="500" placeholder="Describe the meeting purpose..." required>{{ old('purpose') }}</textarea>
                                <div class="form-text">
                                    <span id="purposeCount">0</span>/500 characters
                                </div>
                                @error('purpose')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Booking on Behalf (Admin/Director only) --}}
                            @if (auth()->user()->canManageBookings())
                                <div class="mb-3">
                                    <label class="form-label" for="user_id">Book on Behalf of (Optional)</label>
                                    <select class="form-select" id="user_id" name="user_id">
                                        <option value="">Myself ({{ auth()->user()->name }})</option>
                                        @foreach (\App\Models\User::active()->where('id', '!=', auth()->id())->orderBy('name')->get() as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Leave empty to book for yourself</div>
                                </div>
                            @endif

                            {{-- Submit Buttons --}}
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                    <i class="bx bx-check me-1"></i> Confirm Booking
                                </button>
                                <a href="{{ route('bookings.create-recurring') }}" class="btn btn-outline-info me-2">
                                    <i class="bx bx-repeat me-1"></i> Recurring Booking
                                </a>
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Room Preview Panel --}}
            <div class="col-md-4">
                <div class="card mb-4" id="roomPreview" style="display: none;">
                    <img id="roomImage" src="" class="card-img-top" alt="Room Image"
                        style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title" id="roomName">-</h5>
                        <p class="card-text">
                            <i class="bx bx-user me-1"></i> <span id="roomCapacity">-</span> seats<br>
                            <i class="bx bx-map me-1"></i> <span id="roomLocation">-</span>
                        </p>
                        <div id="roomAmenities"></div>
                    </div>
                </div>

                {{-- Booking Guidelines --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Booking Guidelines</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="bx bx-time text-primary me-1"></i> Operating hours: 8:00 AM -
                                6:00 PM
                            </li>
                            <li class="mb-2"><i class="bx bx-timer text-primary me-1"></i> Duration: 30 min to 8 hours
                            </li>
                            <li class="mb-2"><i class="bx bx-calendar text-primary me-1"></i> Book any future date</li>
                            <li class="mb-2"><i class="bx bx-check-circle text-success me-1"></i> Instant confirmation
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('bookingForm');
                const roomSelect = document.getElementById('room_id');
                const dateInput = document.getElementById('booking_date');
                const startTimeSelect = document.getElementById('start_time');
                const endTimeSelect = document.getElementById('end_time');
                const purposeInput = document.getElementById('purpose');
                const submitBtn = document.getElementById('submitBtn');

                let availabilityCheckTimeout;

                // Room selection change
                roomSelect.addEventListener('change', function() {
                    updateRoomPreview();
                    checkAvailability();
                });

                // Time/date change triggers availability check
                [dateInput, startTimeSelect, endTimeSelect].forEach(el => {
                    el.addEventListener('change', function() {
                        updateDuration();
                        checkAvailability();
                    });
                });

                // Purpose character count
                purposeInput.addEventListener('input', function() {
                    document.getElementById('purposeCount').textContent = this.value.length;
                });

                function updateDuration() {
                    const start = startTimeSelect.value;
                    const end = endTimeSelect.value;
                    const display = document.getElementById('durationDisplay');

                    if (start && end) {
                        const [sh, sm] = start.split(':').map(Number);
                        const [eh, em] = end.split(':').map(Number);
                        const minutes = (eh * 60 + em) - (sh * 60 + sm);

                        if (minutes > 0) {
                            const hours = Math.floor(minutes / 60);
                            const mins = minutes % 60;
                            display.textContent = hours > 0 ?
                                `${hours}h ${mins > 0 ? mins + 'm' : ''}` :
                                `${mins}m`;
                        } else {
                            display.textContent = 'Invalid';
                        }
                    } else {
                        display.textContent = '--';
                    }
                }

                function checkAvailability() {
                    clearTimeout(availabilityCheckTimeout);

                    const roomId = roomSelect.value;
                    const date = dateInput.value;
                    const startTime = startTimeSelect.value;
                    const endTime = endTimeSelect.value;

                    if (!roomId || !date || !startTime || !endTime) {
                        hideAvailabilityStatus();
                        return;
                    }

                    // Show loading state
                    const container = document.getElementById('availabilityStatus');
                    const message = document.getElementById('availabilityMessage');
                    container.style.display = 'block';
                    message.className = 'alert alert-secondary';
                    message.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span> Checking availability...';
                    submitBtn.disabled = true;

                    availabilityCheckTimeout = setTimeout(() => {
                        fetch('{{ route('ajax.availability.check') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    room_id: roomId,
                                    date: date,
                                    start_time: startTime,
                                    end_time: endTime,
                                }),
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network error');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.available) {
                                    message.className = 'alert alert-success';
                                    message.innerHTML =
                                        '<i class="bx bx-check-circle me-1"></i> <strong>Available!</strong> This time slot is free.';
                                    submitBtn.disabled = false;
                                } else {
                                    message.className = 'alert alert-danger';
                                    let errorHtml =
                                        `<i class="bx bx-x-circle me-1"></i> <strong>Not Available</strong><br>${data.message}`;

                                    // Show conflict details if available
                                    if (data.conflict) {
                                        errorHtml +=
                                            `<br><small class="text-muted">Existing booking: ${data.conflict.time} (${data.conflict.reference})</small>`;
                                    }

                                    message.innerHTML = errorHtml;
                                    submitBtn.disabled = true;
                                }
                            })
                            .catch(error => {
                                console.error('Availability check failed:', error);
                                message.className = 'alert alert-warning';
                                message.innerHTML =
                                    '<i class="bx bx-error me-1"></i> Could not verify availability. Please try again.';
                                submitBtn.disabled = false; // Allow submission, server will validate
                            });
                    }, 300); // Debounce 300ms
                }

                function hideAvailabilityStatus() {
                    document.getElementById('availabilityStatus').style.display = 'none';
                    submitBtn.disabled = false;
                }

                function updateRoomPreview() {
                    const selected = roomSelect.options[roomSelect.selectedIndex];
                    const preview = document.getElementById('roomPreview');

                    if (roomSelect.value) {
                        preview.style.display = 'block';
                        document.getElementById('roomName').textContent = selected.text.split(' (')[0];
                        document.getElementById('roomCapacity').textContent = selected.dataset.capacity;
                        document.getElementById('roomLocation').textContent = selected.dataset.location;
                        document.getElementById('roomImage').src = selected.dataset.image ||
                            '{{ asset('assets/img/rooms/placeholder.png') }}';
                    } else {
                        preview.style.display = 'none';
                    }
                }

                // Form submission with loading state
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1"></span> Creating...';
                });

                // Initialize
                updateDuration();
                document.getElementById('purposeCount').textContent = purposeInput.value.length;
                if (roomSelect.value) {
                    updateRoomPreview();
                    checkAvailability();
                }
            });
        </script>
    @endpush
@endsection
