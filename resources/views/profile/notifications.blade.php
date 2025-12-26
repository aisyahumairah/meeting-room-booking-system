@extends('layouts.app')

@section('title', 'Notification Preferences')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class='bx bx-bell me-2'></i>Notification Preferences
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Choose which email notifications you want to receive.
                            Note: Some critical notifications cannot be disabled.
                        </p>

                        <form action="{{ route('profile.notifications.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="booking_confirmed"
                                    name="booking_confirmed" value="1"
                                    {{ $preferences->booking_confirmed ? 'checked' : '' }}>
                                <label class="form-check-label" for="booking_confirmed">
                                    Booking Confirmed
                                </label>
                                <div class="form-text">Receive email when your booking is confirmed</div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="booking_cancelled"
                                    name="booking_cancelled" value="1"
                                    {{ $preferences->booking_cancelled ? 'checked' : '' }}>
                                <label class="form-check-label" for="booking_cancelled">
                                    Booking Cancelled
                                </label>
                                <div class="form-text">Receive email when your booking is cancelled</div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="booking_reminder"
                                    name="booking_reminder" value="1"
                                    {{ $preferences->booking_reminder ? 'checked' : '' }}>
                                <label class="form-check-label" for="booking_reminder">
                                    Booking Reminders
                                </label>
                                <div class="form-text">Receive reminder 24 hours before your booking</div>
                            </div>

                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="room_status_changed"
                                    name="room_status_changed" value="1"
                                    {{ $preferences->room_status_changed ? 'checked' : '' }}>
                                <label class="form-check-label" for="room_status_changed">
                                    Room Status Changes
                                </label>
                                <div class="form-text">Receive email when a room with your booking changes status</div>
                            </div>

                            <div class="alert alert-info mb-4">
                                <i class='bx bx-info-circle me-1'></i>
                                <strong>Non-optional notifications:</strong> Welcome emails and password reset
                                emails will always be sent for security purposes.
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">
                                    <i class='bx bx-arrow-back me-1'></i> Back to Profile
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class='bx bx-save me-1'></i> Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
