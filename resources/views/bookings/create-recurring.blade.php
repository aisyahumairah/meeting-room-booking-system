@extends('layouts.app')

@section('title', 'Create Recurring Booking')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Bookings /</span> Create Recurring Booking
        </h4>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-repeat me-2"></i>Recurring Booking
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="recurringForm" action="{{ route('bookings.store-recurring') }}" method="POST">
                            @csrf

                            {{-- Room Selection --}}
                            <div class="mb-3">
                                <label class="form-label" for="room_id">Meeting Room</label>
                                <select class="form-select @error('room_id') is-invalid @enderror" id="room_id"
                                    name="room_id" required>
                                    <option value="">Select a room...</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}"
                                            {{ old('room_id', $selectedRoom?->id) == $room->id ? 'selected' : '' }}>
                                            {{ $room->name }} ({{ $room->capacity }} seats, {{ $room->floor_location }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Start Date --}}
                            <div class="mb-3">
                                <label class="form-label" for="start_date">Start Date</label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                    id="start_date" name="start_date" min="{{ date('Y-m-d') }}"
                                    value="{{ old('start_date') }}" required onkeydown="return false;">
                                @error('start_date')
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
                                                    {{ old('start_time') == $time ? 'selected' : '' }}>
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
                                                    {{ old('end_time') == $time ? 'selected' : '' }}>
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

                            <hr class="my-4">

                            {{-- Recurrence Type --}}
                            <h6 class="mb-3">Recurrence Pattern</h6>

                            <div class="mb-3">
                                <label class="form-label">Repeat</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recurrence_type"
                                            id="recurrence_daily" value="daily"
                                            {{ old('recurrence_type', 'weekly') == 'daily' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recurrence_daily">Daily</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recurrence_type"
                                            id="recurrence_weekly" value="weekly"
                                            {{ old('recurrence_type', 'weekly') == 'weekly' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recurrence_weekly">Weekly</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recurrence_type"
                                            id="recurrence_monthly" value="monthly"
                                            {{ old('recurrence_type') == 'monthly' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="recurrence_monthly">Monthly</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Interval --}}
                            <div class="mb-3">
                                <label class="form-label" for="recurrence_interval">Every</label>
                                <div class="input-group" style="max-width: 200px;">
                                    <input type="number" class="form-control" id="recurrence_interval"
                                        name="recurrence_interval" value="{{ old('recurrence_interval', 1) }}"
                                        min="1" max="12">
                                    <span class="input-group-text" id="intervalLabel">week(s)</span>
                                </div>
                            </div>

                            {{-- Weekly: Days of Week --}}
                            <div id="weeklyOptions" class="mb-3" style="display: none;">
                                <label class="form-label">On Days</label>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach (['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5] as $day => $value)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="days_of_week[]"
                                                value="{{ $value }}" id="day_{{ $value }}"
                                                {{ in_array($value, old('days_of_week', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label"
                                                for="day_{{ $value }}">{{ $day }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('days_of_week')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Monthly: Day of Month --}}
                            <div id="monthlyOptions" class="mb-3" style="display: none;">
                                <label class="form-label" for="day_of_month">Day of Month</label>
                                <select class="form-select" id="day_of_month" name="day_of_month"
                                    style="max-width: 150px;">
                                    @for ($i = 1; $i <= 31; $i++)
                                        <option value="{{ $i }}"
                                            {{ old('day_of_month') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>

                            <hr class="my-4">

                            {{-- End Condition --}}
                            <h6 class="mb-3">End Recurrence</h6>

                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="end_type" id="end_by_date"
                                        value="by_date" {{ old('end_type', 'by_date') == 'by_date' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="end_by_date">
                                        By Date
                                    </label>
                                </div>
                                <input type="date" class="form-control mb-3" id="end_date" name="end_date"
                                    value="{{ old('end_date') }}" style="max-width: 200px; margin-left: 20px;"
                                    onkeydown="return false;">

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="end_type"
                                        id="end_by_occurrences" value="by_occurrences"
                                        {{ old('end_type') == 'by_occurrences' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="end_by_occurrences">
                                        After X Occurrences
                                    </label>
                                </div>
                                <div class="input-group" style="max-width: 200px; margin-left: 20px;">
                                    <input type="number" class="form-control" id="occurrences" name="occurrences"
                                        value="{{ old('occurrences', 10) }}" min="2" max="52">
                                    <span class="input-group-text">times</span>
                                </div>
                            </div>

                            <hr class="my-4">

                            {{-- Purpose --}}
                            <div class="mb-3">
                                <label class="form-label" for="purpose">Purpose of Booking</label>
                                <textarea class="form-control @error('purpose') is-invalid @enderror" id="purpose" name="purpose" rows="3"
                                    maxlength="500" required>{{ old('purpose') }}</textarea>
                                @error('purpose')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Booking on Behalf (Admin/Director only) --}}
                            @if (auth()->user()->canManageBookings())
                                <div class="mb-3">
                                    <label class="form-label" for="user_id">Book on Behalf of (Optional)</label>
                                    <select class="form-select @error('user_id') is-invalid @enderror" id="user_id"
                                        name="user_id">
                                        <option value="">Myself ({{ auth()->user()->name }})</option>
                                        @foreach (\App\Models\User::active()->where('id', '!=', auth()->id())->orderBy('name')->get() as $user)
                                            <option value="{{ $user->id }}"
                                                {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Leave empty to book for yourself</div>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            {{-- Submit --}}
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                    <i class="bx bx-check me-1"></i> Create Recurring Booking
                                </button>
                                <a href="{{ route('bookings.create') }}" class="btn btn-outline-secondary">
                                    Switch to One-Time Booking
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Preview Panel --}}
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-calendar me-1"></i> Booking Preview</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Select recurrence options to preview dates.</p>

                        <div id="previewLoading" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            Calculating dates...
                        </div>

                        <div id="previewContent" style="display: none;">
                            <div class="alert alert-info">
                                <strong id="previewCount">0</strong> bookings will be created
                            </div>

                            <div id="previewDates" class="small" style="max-height: 300px; overflow-y: auto;">
                                {{-- Dates will be populated here --}}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Guidelines --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Guidelines</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2"><i class="bx bx-check text-success me-1"></i> All occurrences confirmed
                                instantly</li>
                            <li class="mb-2"><i class="bx bx-calendar-x text-warning me-1"></i> Max 1 year from start
                                date</li>
                            <li class="mb-2"><i class="bx bx-link text-info me-1"></i> Edit/cancel applies to entire
                                series</li>
                            <li class="mb-2"><i class="bx bx-error text-danger me-1"></i> All dates must be available
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Availability Summary (Real-time check) --}}
                <div class="card" id="availabilityPanel" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bx bx-time-five me-1"></i> Availability Status</h6>
                        <span id="availabilityBadge"></span>
                    </div>
                    <div class="card-body">
                        <div id="availabilitySummary" class="small">
                            {{-- Summary message here --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('recurringForm');
                const recurrenceTypes = document.querySelectorAll('input[name="recurrence_type"]');
                const weeklyOptions = document.getElementById('weeklyOptions');
                const monthlyOptions = document.getElementById('monthlyOptions');
                const intervalLabel = document.getElementById('intervalLabel');

                let previewTimeout;

                // Toggle recurrence options
                recurrenceTypes.forEach(radio => {
                    radio.addEventListener('change', function() {
                        updateRecurrenceUI();
                        updatePreview();
                    });
                });

                function updateRecurrenceUI() {
                    const type = document.querySelector('input[name="recurrence_type"]:checked')?.value;

                    weeklyOptions.style.display = type === 'weekly' ? 'block' : 'none';
                    monthlyOptions.style.display = type === 'monthly' ? 'block' : 'none';

                    intervalLabel.textContent = type === 'daily' ? 'day(s)' :
                        type === 'weekly' ? 'week(s)' : 'month(s)';
                }

                // Initialize UI
                updateRecurrenceUI();

                // Live preview
                const inputs = form.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.addEventListener('change', () => {
                        clearTimeout(previewTimeout);
                        previewTimeout = setTimeout(updatePreview, 500);
                    });
                });

                function updatePreview() {
                    // Collect form data
                    const formData = new FormData(form);

                    // Basic validation before request
                    if (!formData.get('start_date')) return;

                    document.getElementById('previewLoading').style.display = 'block';
                    document.getElementById('previewContent').style.display = 'none';

                    fetch('{{ route('ajax.bookings.preview-recurrence') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('previewLoading').style.display = 'none';
                            document.getElementById('previewContent').style.display = 'block';
                            document.getElementById('previewCount').textContent = data.count;

                            const datesDiv = document.getElementById('previewDates');
                            datesDiv.innerHTML = ''; // Clear existing content

                            const availPanel = document.getElementById('availabilityPanel');
                            const availBadge = document.getElementById('availabilityBadge');
                            const availSummary = document.getElementById('availabilitySummary');

                            if (data.count > 0) {
                                availPanel.style.display = 'block';

                                if (data.all_available) {
                                    availBadge.innerHTML = '<span class="badge bg-label-success">Available</span>';
                                    availSummary.innerHTML =
                                        `<div class="text-success"><i class="bx bx-check-double me-1"></i> All ${data.count} occurrences are available for the selected room and time.</div>`;
                                    submitBtn.disabled = false;
                                } else {
                                    availBadge.innerHTML =
                                        `<span class="badge bg-label-danger">${data.conflicts} Conflict(s)</span>`;
                                    availSummary.innerHTML =
                                        `<div class="text-danger"><i class="bx bx-error me-1"></i> Room is not available on ${data.conflicts} of the ${data.count} selected dates. Please adjust your selection.</div>`;
                                    submitBtn.disabled = true;
                                }

                                data.dates.forEach(date => {
                                    const div = document.createElement('div');
                                    div.className =
                                        `d-flex justify-content-between align-items-center mb-1 p-1 rounded ${date.is_available ? '' : 'bg-label-danger'}`;
                                    div.innerHTML = `
                                <span>${date.formatted}</span>
                                ${date.is_available
                                    ? '<i class="bx bx-check text-success" title="Available"></i>'
                                    : `<i class="bx bx-x text-danger" title="Conflict: ${date.conflict_type}"></i>`}
                            `;
                                    datesDiv.appendChild(div);
                                });
                            } else {
                                availPanel.style.display = 'none';
                                submitBtn.disabled = true;
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching preview:', error);
                            document.getElementById('previewLoading').style.display = 'none';
                        });
                }
            });
        </script>
    @endpush
@endsection
