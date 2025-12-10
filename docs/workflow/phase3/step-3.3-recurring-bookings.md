# Step 3.3: Recurring Bookings

**Priority:** HIGH | **Ref:** §6.2.2 | **Dependencies:** Step 3.2  
**Status:** TODO

---

## Objective

Implement recurring booking support with daily, weekly, and monthly patterns. All occurrences are created with "confirmed" status (auto-approval).

---

## Task 3.3.1: Create Recurring Booking Request

```bash
php artisan make:request StoreRecurringBookingRequest
```

**File:** `app/Http/Requests/StoreRecurringBookingRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Room;
use Carbon\Carbon;

class StoreRecurringBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['required', 'string', 'max:500'],
            
            // Recurrence settings
            'recurrence_type' => ['required', 'in:daily,weekly,monthly'],
            'recurrence_interval' => ['required', 'integer', 'min:1', 'max:12'],
            
            // Weekly specific
            'days_of_week' => ['required_if:recurrence_type,weekly', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            
            // Monthly specific
            'day_of_month' => ['required_if:recurrence_type,monthly', 'integer', 'between:1,31'],
            
            // End condition
            'end_type' => ['required', 'in:by_date,by_occurrences'],
            'end_date' => ['required_if:end_type,by_date', 'date', 'after:start_date'],
            'occurrences' => ['required_if:end_type,by_occurrences', 'integer', 'min:2', 'max:52'],
            
            // Optional
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $this->validateOperatingHours($validator);
            $this->validateDuration($validator);
            $this->validateRoomStatus($validator);
            $this->validateMaxDuration($validator);
        });
    }

    protected function validateOperatingHours($validator): void
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        
        if ($startTime->lt(Carbon::parse('08:00')) || $startTime->gte(Carbon::parse('18:00'))) {
            $validator->errors()->add('start_time', 'Start time must be between 08:00 and 18:00.');
        }

        if ($endTime->lte(Carbon::parse('08:00')) || $endTime->gt(Carbon::parse('18:00'))) {
            $validator->errors()->add('end_time', 'End time must be between 08:00 and 18:00.');
        }
    }

    protected function validateDuration($validator): void
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        $durationMinutes = $startTime->diffInMinutes($endTime);

        if ($durationMinutes < 30) {
            $validator->errors()->add('end_time', 'Minimum booking duration is 30 minutes.');
        }

        if ($durationMinutes > 480) {
            $validator->errors()->add('end_time', 'Maximum booking duration is 8 hours.');
        }
    }

    protected function validateRoomStatus($validator): void
    {
        $room = Room::find($this->room_id);
        
        if ($room && $room->status !== 'active') {
            $validator->errors()->add('room_id', 'This room is not available for booking.');
        }
    }

    protected function validateMaxDuration($validator): void
    {
        // Calculate end date based on end_type
        $startDate = Carbon::parse($this->start_date);
        $maxDate = $startDate->copy()->addYear();

        if ($this->end_type === 'by_date') {
            $endDate = Carbon::parse($this->end_date);
            if ($endDate->gt($maxDate)) {
                $validator->errors()->add('end_date', 'Recurring bookings cannot exceed 1 year from start date.');
            }
        }
    }

    public function messages(): array
    {
        return [
            'days_of_week.required_if' => 'Please select at least one day of the week.',
            'day_of_month.required_if' => 'Please select the day of the month.',
            'end_date.required_if' => 'Please specify an end date.',
            'occurrences.required_if' => 'Please specify the number of occurrences.',
        ];
    }
}
```

---

## Task 3.3.2: Add Recurring Booking Methods to BookingService

**File:** `app/Services/BookingService.php`

Add these methods to the existing BookingService:

```php
use App\Models\BookingSeries;
use Carbon\CarbonPeriod;

/**
 * Create a recurring booking series with auto-approval
 * 
 * @throws \Exception if any occurrence conflicts
 */
public function createRecurringBooking(array $data, User $booker): BookingSeries
{
    // Calculate all occurrence dates
    $occurrenceDates = $this->calculateOccurrences($data);

    if (empty($occurrenceDates)) {
        throw new \Exception('No valid occurrence dates found for the recurrence pattern.');
    }

    // Check availability for ALL occurrences first
    $conflicts = $this->checkAllOccurrencesAvailability(
        $data['room_id'],
        $occurrenceDates,
        $data['start_time'],
        $data['end_time']
    );

    if (!empty($conflicts)) {
        throw new \Exception(
            'Room is not available on the following dates: ' . 
            implode(', ', array_map(fn($d) => $d->format('M d, Y'), $conflicts))
        );
    }

    return DB::transaction(function () use ($data, $booker, $occurrenceDates) {
        // Lock the room to prevent race conditions
        $room = Room::lockForUpdate()->findOrFail($data['room_id']);

        // Determine the PIC
        $userId = $data['user_id'] ?? $booker->id;

        // Create the series record
        $series = BookingSeries::create([
            'reference_number' => BookingSeries::generateReferenceNumber(),
            'user_id' => $userId,
            'room_id' => $room->id,
            'recurrence_type' => $data['recurrence_type'],
            'recurrence_pattern' => $this->buildRecurrencePattern($data),
            'start_date' => $occurrenceDates[0],
            'end_date' => end($occurrenceDates),
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'purpose' => $data['purpose'],
        ]);

        // Create individual bookings for each occurrence
        foreach ($occurrenceDates as $date) {
            Booking::create([
                'reference_number' => Booking::generateReferenceNumber(),
                'user_id' => $userId,
                'room_id' => $room->id,
                'series_id' => $series->id,
                'booking_date' => $date,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
                'status' => 'confirmed', // Auto-approval
            ]);
        }

        Log::info('Recurring booking created', [
            'series_id' => $series->id,
            'reference' => $series->reference_number,
            'user_id' => $userId,
            'room_id' => $room->id,
            'occurrences' => count($occurrenceDates),
        ]);

        // TODO: Send single confirmation email for series (Phase 4)

        return $series;
    });
}

/**
 * Calculate all occurrence dates based on recurrence pattern
 */
public function calculateOccurrences(array $data): array
{
    $startDate = Carbon::parse($data['start_date']);
    $dates = [];

    // Calculate end date based on end_type
    if ($data['end_type'] === 'by_date') {
        $endDate = Carbon::parse($data['end_date']);
    } else {
        // by_occurrences - will calculate during iteration
        $endDate = $startDate->copy()->addYear(); // Max limit
    }

    $maxOccurrences = $data['end_type'] === 'by_occurrences' 
        ? (int) $data['occurrences'] 
        : 365; // Maximum safety limit

    $interval = (int) ($data['recurrence_interval'] ?? 1);

    switch ($data['recurrence_type']) {
        case 'daily':
            $current = $startDate->copy();
            while ($current->lte($endDate) && count($dates) < $maxOccurrences) {
                $dates[] = $current->copy();
                $current->addDays($interval);
            }
            break;

        case 'weekly':
            $daysOfWeek = $data['days_of_week'] ?? [];
            $current = $startDate->copy()->startOfWeek();
            
            while ($current->lte($endDate) && count($dates) < $maxOccurrences) {
                foreach ($daysOfWeek as $dayOfWeek) {
                    $day = $current->copy()->setISOWeekday($dayOfWeek);
                    if ($day->gte($startDate) && $day->lte($endDate) && count($dates) < $maxOccurrences) {
                        $dates[] = $day->copy();
                    }
                }
                $current->addWeeks($interval);
            }
            
            // Sort dates chronologically
            usort($dates, fn($a, $b) => $a->timestamp <=> $b->timestamp);
            break;

        case 'monthly':
            $dayOfMonth = (int) ($data['day_of_month'] ?? 1);
            $current = $startDate->copy()->setDay(min($dayOfMonth, $startDate->daysInMonth));
            
            if ($current->lt($startDate)) {
                $current->addMonthsNoOverflow($interval);
            }

            while ($current->lte($endDate) && count($dates) < $maxOccurrences) {
                // Handle months with fewer days
                $adjusted = $current->copy();
                if ($dayOfMonth > $adjusted->daysInMonth) {
                    $adjusted->setDay($adjusted->daysInMonth);
                } else {
                    $adjusted->setDay($dayOfMonth);
                }
                
                $dates[] = $adjusted;
                $current->addMonthsNoOverflow($interval);
            }
            break;
    }

    return $dates;
}

/**
 * Check availability for all occurrences and return conflicting dates
 */
protected function checkAllOccurrencesAvailability(
    int $roomId, 
    array $dates, 
    string $startTime, 
    string $endTime
): array {
    $conflicts = [];
    $room = Room::find($roomId);

    foreach ($dates as $date) {
        $dateString = $date->format('Y-m-d');
        
        if (!$room->isAvailable($dateString, $startTime, $endTime)) {
            $conflicts[] = $date;
        }
    }

    return $conflicts;
}

/**
 * Build recurrence pattern for storage
 */
protected function buildRecurrencePattern(array $data): array
{
    $pattern = [
        'interval' => (int) ($data['recurrence_interval'] ?? 1),
    ];

    switch ($data['recurrence_type']) {
        case 'weekly':
            $pattern['days_of_week'] = array_map('intval', $data['days_of_week'] ?? []);
            break;
        case 'monthly':
            $pattern['day_of_month'] = (int) ($data['day_of_month'] ?? 1);
            break;
    }

    return $pattern;
}

/**
 * Cancel an entire booking series
 */
public function cancelSeries(BookingSeries $series, string $reason, User $cancelledBy): void
{
    DB::transaction(function () use ($series, $reason, $cancelledBy) {
        $series->bookings()
            ->where('status', 'confirmed')
            ->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => $cancelledBy->id,
                'cancelled_at' => now(),
            ]);
    });

    Log::info('Booking series cancelled', [
        'series_id' => $series->id,
        'cancelled_by' => $cancelledBy->id,
        'reason' => $reason,
    ]);
}
```

---

## Task 3.3.3: Add Recurring Booking Controller Methods

**File:** `app/Http/Controllers/BookingController.php`

Add these methods:

```php
use App\Http\Requests\StoreRecurringBookingRequest;
use App\Models\BookingSeries;

/**
 * Show recurring booking creation form
 */
public function createRecurring(Request $request)
{
    $rooms = Room::active()
        ->with('amenities')
        ->orderBy('name')
        ->get();

    $selectedRoom = $request->has('room_id') 
        ? Room::find($request->room_id) 
        : null;

    return view('bookings.create-recurring', compact('rooms', 'selectedRoom'));
}

/**
 * Store a recurring booking series
 */
public function storeRecurring(StoreRecurringBookingRequest $request)
{
    try {
        $series = $this->bookingService->createRecurringBooking(
            $request->validated(),
            auth()->user()
        );

        // Log to audit trail
        $this->auditService->log(
            'booking_series_created',
            'booking_series',
            $series->id,
            [
                'reference' => $series->reference_number,
                'room' => $series->room->name,
                'start_date' => $series->start_date->format('Y-m-d'),
                'end_date' => $series->end_date->format('Y-m-d'),
                'occurrences' => $series->bookings()->count(),
                'recurrence' => $series->recurrence_description,
            ]
        );

        return redirect()
            ->route('my-bookings')
            ->with('success', "Recurring booking confirmed! {$series->bookings()->count()} bookings created. Reference: {$series->reference_number}");

    } catch (\Exception $e) {
        return back()
            ->withInput()
            ->withErrors(['room_id' => $e->getMessage()]);
    }
}

/**
 * Preview recurrence dates via AJAX
 */
public function previewRecurrence(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'recurrence_type' => 'required|in:daily,weekly,monthly',
        'recurrence_interval' => 'required|integer|min:1',
        'days_of_week' => 'array',
        'day_of_month' => 'integer|between:1,31',
        'end_type' => 'required|in:by_date,by_occurrences',
        'end_date' => 'date',
        'occurrences' => 'integer|min:2|max:52',
    ]);

    $dates = $this->bookingService->calculateOccurrences($request->all());

    return response()->json([
        'dates' => array_map(fn($d) => [
            'date' => $d->format('Y-m-d'),
            'formatted' => $d->format('D, M d, Y'),
            'day_name' => $d->format('l'),
        ], $dates),
        'count' => count($dates),
    ]);
}
```

---

## Task 3.3.4: Add Recurring Booking Routes

**File:** `routes/web.php`

Add to the authenticated routes:

```php
Route::middleware(['auth', 'check.active'])->group(function () {
    // ... existing routes ...

    // Recurring booking
    Route::get('/bookings/create-recurring', [BookingController::class, 'createRecurring'])
        ->name('bookings.create-recurring');
    Route::post('/bookings/recurring', [BookingController::class, 'storeRecurring'])
        ->name('bookings.store-recurring');
    
    // AJAX preview
    Route::post('/ajax/bookings/preview-recurrence', [BookingController::class, 'previewRecurrence'])
        ->name('ajax.bookings.preview-recurrence');
});
```

---

## Task 3.3.5: Create Recurring Booking Form View

**File:** `resources/views/bookings/create-recurring.blade.php`

```blade
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
                            <select class="form-select @error('room_id') is-invalid @enderror" 
                                    id="room_id" name="room_id" required>
                                <option value="">Select a room...</option>
                                @foreach($rooms as $room)
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
                            <input type="date" 
                                   class="form-control @error('start_date') is-invalid @enderror" 
                                   id="start_date" 
                                   name="start_date" 
                                   min="{{ date('Y-m-d') }}"
                                   value="{{ old('start_date') }}"
                                   required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Time Selection --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="start_time">Start Time</label>
                                <select class="form-select @error('start_time') is-invalid @enderror" 
                                        id="start_time" name="start_time" required>
                                    <option value="">Select start time...</option>
                                    @for($hour = 8; $hour < 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @php $time = sprintf('%02d:%s', $hour, $minute); @endphp
                                            <option value="{{ $time }}" {{ old('start_time') == $time ? 'selected' : '' }}>
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
                                    <option value="">Select end time...</option>
                                    @for($hour = 8; $hour <= 18; $hour++)
                                        @foreach(['00', '30'] as $minute)
                                            @if($hour == 8 && $minute == '00') @continue @endif
                                            @php $time = sprintf('%02d:%s', $hour, $minute); @endphp
                                            <option value="{{ $time }}" {{ old('end_time') == $time ? 'selected' : '' }}>
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
                                @foreach(['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5] as $day => $value)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="days_of_week[]" 
                                               value="{{ $value }}" id="day_{{ $value }}"
                                               {{ in_array($value, old('days_of_week', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="day_{{ $value }}">{{ $day }}</label>
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
                            <select class="form-select" id="day_of_month" name="day_of_month" style="max-width: 150px;">
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}" {{ old('day_of_month') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <hr class="my-4">

                        {{-- End Condition --}}
                        <h6 class="mb-3">End Recurrence</h6>
                        
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="end_type" 
                                       id="end_by_date" value="by_date"
                                       {{ old('end_type', 'by_date') == 'by_date' ? 'checked' : '' }}>
                                <label class="form-check-label" for="end_by_date">
                                    By Date
                                </label>
                            </div>
                            <input type="date" class="form-control mb-3" id="end_date" name="end_date"
                                   value="{{ old('end_date') }}" style="max-width: 200px; margin-left: 20px;">
                            
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
                            <textarea class="form-control @error('purpose') is-invalid @enderror" 
                                      id="purpose" name="purpose" rows="3" maxlength="500"
                                      required>{{ old('purpose') }}</textarea>
                            @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

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
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="bx bx-check text-success me-1"></i> All occurrences confirmed instantly</li>
                        <li class="mb-2"><i class="bx bx-calendar-x text-warning me-1"></i> Max 1 year from start date</li>
                        <li class="mb-2"><i class="bx bx-link text-info me-1"></i> Edit/cancel applies to entire series</li>
                        <li class="mb-2"><i class="bx bx-error text-danger me-1"></i> All dates must be available</li>
                    </ul>
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

    // Update preview on any change
    const previewTriggers = [
        'start_date', 'recurrence_interval', 'end_date', 'occurrences', 'day_of_month'
    ];
    
    previewTriggers.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', updatePreview);
    });

    document.querySelectorAll('input[name="days_of_week[]"]').forEach(el => {
        el.addEventListener('change', updatePreview);
    });

    document.querySelectorAll('input[name="end_type"]').forEach(el => {
        el.addEventListener('change', updatePreview);
    });

    function updatePreview() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(fetchPreview, 500);
    }

    function fetchPreview() {
        const startDate = document.getElementById('start_date').value;
        const recurrenceType = document.querySelector('input[name="recurrence_type"]:checked')?.value;
        const endType = document.querySelector('input[name="end_type"]:checked')?.value;

        if (!startDate || !recurrenceType || !endType) return;

        const data = {
            start_date: startDate,
            recurrence_type: recurrenceType,
            recurrence_interval: document.getElementById('recurrence_interval').value,
            end_type: endType,
        };

        if (recurrenceType === 'weekly') {
            data.days_of_week = Array.from(document.querySelectorAll('input[name="days_of_week[]"]:checked'))
                .map(el => el.value);
        }

        if (recurrenceType === 'monthly') {
            data.day_of_month = document.getElementById('day_of_month').value;
        }

        if (endType === 'by_date') {
            data.end_date = document.getElementById('end_date').value;
        } else {
            data.occurrences = document.getElementById('occurrences').value;
        }

        document.getElementById('previewLoading').style.display = 'block';
        document.getElementById('previewContent').style.display = 'none';

        fetch('{{ route("ajax.bookings.preview-recurrence") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(result => {
            document.getElementById('previewLoading').style.display = 'none';
            document.getElementById('previewContent').style.display = 'block';
            document.getElementById('previewCount').textContent = result.count;
            
            const container = document.getElementById('previewDates');
            container.innerHTML = result.dates.slice(0, 20).map(d => 
                `<div class="p-1 border-bottom">${d.formatted}</div>`
            ).join('');
            
            if (result.count > 20) {
                container.innerHTML += `<div class="p-1 text-muted">... and ${result.count - 20} more</div>`;
            }
        })
        .catch(error => {
            console.error('Preview failed:', error);
        });
    }

    // Initialize
    updateRecurrenceUI();
});
</script>
@endpush
@endsection
```

---

## Testing Requirements

**File:** `tests/Feature/RecurringBookingTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\BookingSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecurringBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['status' => 'active']);
        $this->room = Room::factory()->create(['status' => 'active']);
    }

    public function test_can_create_weekly_recurring_booking()
    {
        $response = $this->actingAs($this->user)
            ->post(route('bookings.store-recurring'), [
                'room_id' => $this->room->id,
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Weekly team sync',
                'recurrence_type' => 'weekly',
                'recurrence_interval' => 1,
                'days_of_week' => [1, 3], // Mon, Wed
                'end_type' => 'by_occurrences',
                'occurrences' => 4,
            ]);

        $response->assertRedirect(route('my-bookings'));
        $this->assertDatabaseCount('booking_series', 1);
        $this->assertEquals(4, Booking::count());
    }

    public function test_all_occurrences_are_confirmed()
    {
        $this->actingAs($this->user)
            ->post(route('bookings.store-recurring'), [
                'room_id' => $this->room->id,
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Daily standup',
                'recurrence_type' => 'daily',
                'recurrence_interval' => 1,
                'end_type' => 'by_occurrences',
                'occurrences' => 5,
            ]);

        $statuses = Booking::pluck('status')->unique();
        $this->assertCount(1, $statuses);
        $this->assertEquals('confirmed', $statuses[0]);
    }

    public function test_conflicts_block_entire_series()
    {
        // Create existing booking
        Booking::factory()->create([
            'room_id' => $this->room->id,
            'booking_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('bookings.store-recurring'), [
                'room_id' => $this->room->id,
                'start_date' => now()->addDays(1)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'purpose' => 'Daily standup',
                'recurrence_type' => 'daily',
                'recurrence_interval' => 1,
                'end_type' => 'by_occurrences',
                'occurrences' => 5,
            ]);

        $response->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('booking_series', 0);
    }
}
```

---

## Acceptance Criteria

- [ ] Recurring booking form displays with all recurrence options
- [ ] Daily, weekly, and monthly patterns work correctly
- [ ] End by date and end by occurrences options work
- [ ] Preview shows calculated occurrence dates
- [ ] All occurrences are created with "confirmed" status
- [ ] Series reference number generated (BK-SERIES-YYYY-NNNNN)
- [ ] If ANY date conflicts, entire series is rejected
- [ ] Conflicting dates are shown in error message
- [ ] Audit log entry created for series
- [ ] All tests pass

---

**Next:** [Step 3.4 - My Bookings](./step-3.4-my-bookings.md)
