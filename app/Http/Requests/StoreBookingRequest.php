<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Room;
use Carbon\Carbon;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \Illuminate\Support\Facades\Auth::check();
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['required', 'string', 'max:500'],
            'user_id' => ['nullable', 'exists:users,id'], // For admin booking on behalf
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
            $this->validateAvailability($validator);
        });
    }

    protected function validateOperatingHours($validator): void
    {
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        $operatingStart = Carbon::parse('08:00');
        $operatingEnd = Carbon::parse('18:00');

        if ($startTime->lt($operatingStart) || $startTime->gte($operatingEnd)) {
            $validator->errors()->add('start_time', 'Start time must be between 08:00 and 18:00.');
        }

        if ($endTime->lte($operatingStart) || $endTime->gt($operatingEnd)) {
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

        if ($durationMinutes > 480) { // 8 hours
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

    protected function validateAvailability($validator): void
    {
        if (!$this->room_id || !$this->booking_date || !$this->start_time || !$this->end_time) {
            return;
        }

        $conflict = \App\Models\Booking::where('room_id', $this->room_id)
            ->where('booking_date', $this->booking_date)
            ->where('status', 'confirmed')
            ->where(function ($query) {
                $start = $this->start_time;
                $end = $this->end_time;

                $query->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            })
            ->exists();

        if ($conflict) {
            $validator->errors()->add('room_id', 'This room is not available at the selected time.');
        }
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Please select a meeting room.',
            'room_id.exists' => 'The selected room does not exist.',
            'booking_date.required' => 'Please select a booking date.',
            'booking_date.after_or_equal' => 'Booking date cannot be in the past.',
            'start_time.required' => 'Please select a start time.',
            'end_time.required' => 'Please select an end time.',
            'end_time.after' => 'End time must be after start time.',
            'purpose.required' => 'Please provide a purpose for this booking.',
            'purpose.max' => 'Purpose cannot exceed 500 characters.',
        ];
    }
}
