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
