<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        $user = auth()->user();

        // Admin/Director can cancel any booking
        if ($user->canManageBookings()) {
            return true;
        }

        // Regular users can only cancel their own confirmed bookings
        return $booking->user_id === $user->id && $booking->is_cancellable;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Please provide a reason for cancellation.',
            'cancellation_reason.max' => 'Cancellation reason cannot exceed 500 characters.',
        ];
    }
}
