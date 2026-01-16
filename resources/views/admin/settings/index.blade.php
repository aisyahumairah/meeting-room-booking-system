@extends('layouts.app')

@section('title', 'System Configuration')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">
                <i class='bx bx-cog me-2'></i>System Configuration
            </h4>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <!-- Session & Security Settings -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class='bx bx-shield-quarter me-2'></i>Session & Security
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="if(confirm('Reset security settings to defaults?')) document.getElementById('resetSecurityForm').submit()">
                                Reset to Defaults
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Session Timeout (minutes)</label>
                                <input type="number" name="session_timeout"
                                    class="form-control @error('session_timeout') is-invalid @enderror"
                                    value="{{ old('session_timeout', $settings['session_timeout']->value ?? 30) }}"
                                    min="15" max="120" required>
                                <div class="form-text">Auto-logout after this period of inactivity (15-120 min)</div>
                                @error('session_timeout')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password Reset Token Expiry (minutes)</label>
                                <input type="number" name="password_reset_expiry"
                                    class="form-control @error('password_reset_expiry') is-invalid @enderror"
                                    value="{{ old('password_reset_expiry', $settings['password_reset_expiry']->value ?? 30) }}"
                                    min="5" max="60" required>
                                <div class="form-text">How long password reset links remain valid (5-60 min)</div>
                                @error('password_reset_expiry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Login Attempt Limit</label>
                                <input type="number" name="login_attempt_limit"
                                    class="form-control @error('login_attempt_limit') is-invalid @enderror"
                                    value="{{ old('login_attempt_limit', $settings['login_attempt_limit']->value ?? 5) }}"
                                    min="3" max="10" required>
                                <div class="form-text">Maximum failed attempts before temporary lockout (3-10)</div>
                                @error('login_attempt_limit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Lockout Duration (minutes)</label>
                                <input type="number" name="lockout_duration"
                                    class="form-control @error('lockout_duration') is-invalid @enderror"
                                    value="{{ old('lockout_duration', $settings['lockout_duration']->value ?? 15) }}"
                                    min="5" max="60" required>
                                <div class="form-text">Account lockout duration after exceeding attempts (5-60 min)</div>
                                @error('lockout_duration')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Password Policy (Read-Only) -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class='bx bx-key me-2'></i>Password Policy
                                <span class="badge bg-secondary ms-2">Read-Only</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class='bx bx-info-circle me-1'></i>
                                These requirements are set by organizational policy and cannot be changed.
                            </div>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class='bx bx-check text-success me-2'></i>
                                    Minimum {{ $passwordPolicy['min_length'] }} characters
                                </li>
                                <li class="mb-2">
                                    <i class='bx bx-check text-success me-2'></i>
                                    Must contain letters
                                </li>
                                <li class="mb-2">
                                    <i class='bx bx-check text-success me-2'></i>
                                    Must contain numbers
                                </li>
                                <li>
                                    <i class='bx bx-check text-success me-2'></i>
                                    Must contain symbols
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Booking Rules (Read-Only) -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class='bx bx-calendar-check me-2'></i>Booking Rules
                                <span class="badge bg-secondary ms-2">Read-Only</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <i class='bx bx-info-circle me-1'></i>
                                These rules are set by organizational policy and cannot be changed.
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <small class="text-muted d-block">Operating Hours</small>
                                    <strong>{{ $bookingRules['operating_hours_start'] }} -
                                        {{ $bookingRules['operating_hours_end'] }}</strong>
                                </div>
                                <div class="col-6 mb-3">
                                    <small class="text-muted d-block">Time Increments</small>
                                    <strong>{{ $bookingRules['time_increments'] }} minutes</strong>
                                </div>
                                <div class="col-6 mb-3">
                                    <small class="text-muted d-block">Minimum Duration</small>
                                    <strong>{{ $bookingRules['min_duration'] }} minutes</strong>
                                </div>
                                <div class="col-6 mb-3">
                                    <small class="text-muted d-block">Maximum Duration</small>
                                    <strong>{{ $bookingRules['max_duration'] / 60 }} hours</strong>
                                </div>
                                <div class="col-6 mb-3">
                                    <small class="text-muted d-block">Advance Booking</small>
                                    <strong>{{ $bookingRules['max_advance_days'] }}</strong>
                                </div>
                                <div class="col-6 mb-3">
                                    <small class="text-muted d-block">Max Recurring Period</small>
                                    <strong>{{ $bookingRules['max_recurring_period'] }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <!-- Notification Settings -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class='bx bx-bell me-2'></i>Notification Settings
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="if(confirm('Reset notification settings to defaults?')) { document.getElementById('resetGroup').value='notifications'; document.getElementById('resetForm').submit(); }">
                                Reset to Defaults
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Master Toggle -->
                            <div class="mb-4 pb-3 border-bottom">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="email_enabled"
                                        name="email_enabled" value="1"
                                        {{ old('email_enabled', $settings['email_enabled']->value ?? 'true') === 'true' || old('email_enabled') === '1' ? 'checked' : '' }}
                                        onchange="toggleNotificationSettings(this.checked)">
                                    <label class="form-check-label fw-bold" for="email_enabled">
                                        Enable Email Notifications
                                    </label>
                                </div>
                                <div class="form-text">Master toggle for all email notifications</div>
                            </div>

                            <!-- Individual Toggles -->
                            <div id="notificationToggles">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input notification-toggle" type="checkbox"
                                        id="notify_welcome_email" name="notify_welcome_email" value="1"
                                        {{ ($settings['notify_welcome_email']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_welcome_email">
                                        Account Creation (Welcome Email)
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input notification-toggle" type="checkbox"
                                        id="notify_password_reset" name="notify_password_reset" value="1"
                                        {{ ($settings['notify_password_reset']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_password_reset">
                                        Password Reset
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input notification-toggle" type="checkbox"
                                        id="notify_booking_confirmed" name="notify_booking_confirmed" value="1"
                                        {{ ($settings['notify_booking_confirmed']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_booking_confirmed">
                                        Booking Confirmed
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input notification-toggle" type="checkbox"
                                        id="notify_booking_cancelled" name="notify_booking_cancelled" value="1"
                                        {{ ($settings['notify_booking_cancelled']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_booking_cancelled">
                                        Booking Cancelled
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input notification-toggle" type="checkbox"
                                        id="notify_booking_reminder" name="notify_booking_reminder" value="1"
                                        {{ ($settings['notify_booking_reminder']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_booking_reminder">
                                        Booking Reminder (24h before)
                                    </label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input notification-toggle" type="checkbox"
                                        id="notify_room_status_changed" name="notify_room_status_changed" value="1"
                                        {{ ($settings['notify_room_status_changed']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="notify_room_status_changed">
                                        Room Status Changed
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Server Configuration -->
                    <div class="card mb-4" id="emailServerSettings">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class='bx bx-server me-2'></i>Email Server Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Mailer</label>
                                <select name="mail_mailer" class="form-select email-server-setting">
                                    <option value="smtp"
                                        {{ ($settings['mail_mailer']->value ?? env('MAIL_MAILER')) === 'smtp' ? 'selected' : '' }}>
                                        SMTP</option>
                                    <option value="log"
                                        {{ ($settings['mail_mailer']->value ?? env('MAIL_MAILER')) === 'log' ? 'selected' : '' }}>
                                        Log (Debug)</option>
                                    <option value="mailgun"
                                        {{ ($settings['mail_mailer']->value ?? env('MAIL_MAILER')) === 'mailgun' ? 'selected' : '' }}>
                                        Mailgun</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Mail Host</label>
                                    <input type="text" name="mail_host" class="form-control email-server-setting"
                                        value="{{ old('mail_host', $settings['mail_host']->value ?? env('MAIL_HOST')) }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Port</label>
                                    <input type="number" name="mail_port" class="form-control email-server-setting"
                                        value="{{ old('mail_port', $settings['mail_port']->value ?? env('MAIL_PORT')) }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="mail_username" class="form-control email-server-setting"
                                    value="{{ old('mail_username', $settings['mail_username']->value ?? env('MAIL_USERNAME')) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" name="mail_password" id="mail_password"
                                        class="form-control email-server-setting"
                                        placeholder="{{ !empty($settings['mail_password']->value) || !empty(env('MAIL_PASSWORD')) ? 'Active (Leave blank to keep)' : '' }}">
                                    <span class="input-group-text" onclick="togglePasswordVisibility('mail_password')"
                                        style="cursor: pointer;">
                                        <i class="bx bx-hide" id="mail_password_icon"></i>
                                    </span>
                                </div>
                                <div class="form-text">Leave blank to keep existing password</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Encryption</label>
                                <select name="mail_encryption" class="form-select email-server-setting">
                                    <option value="tls"
                                        {{ ($settings['mail_encryption']->value ?? env('MAIL_ENCRYPTION')) === 'tls' ? 'selected' : '' }}>
                                        TLS</option>
                                    <option value="ssl"
                                        {{ ($settings['mail_encryption']->value ?? env('MAIL_ENCRYPTION')) === 'ssl' ? 'selected' : '' }}>
                                        SSL</option>
                                    <option value=""
                                        {{ ($settings['mail_encryption']->value ?? env('MAIL_ENCRYPTION')) === null ? 'selected' : '' }}>
                                        None</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">From Address</label>
                                <input type="email" name="mail_from_address" class="form-control email-server-setting"
                                    value="{{ old('mail_from_address', $settings['mail_from_address']->value ?? env('MAIL_FROM_ADDRESS')) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Mode -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class='bx bx-wrench me-2'></i>System Maintenance
                            </h6>
                        </div>
                        <div class="card-body">
                            @php
                                $isMaintenanceOn = ($settings['maintenance_mode']->value ?? 'false') === 'true';
                            @endphp

                            @if ($isMaintenanceOn)
                                <div class="alert alert-warning mb-3">
                                    <i class='bx bx-error-circle me-1'></i>
                                    <strong>Maintenance mode is currently ACTIVE.</strong>
                                    Regular users cannot access the system.
                                </div>
                            @endif

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode"
                                    name="maintenance_mode" value="1" {{ $isMaintenanceOn ? 'checked' : '' }}
                                    onchange="confirmMaintenanceToggle(this)">
                                <label class="form-check-label fw-bold" for="maintenance_mode">
                                    Enable Maintenance Mode
                                </label>
                            </div>
                            <div class="form-text mt-2">
                                When enabled:
                                <ul class="mb-0 mt-1">
                                    <li>Regular users cannot log in</li>
                                    <li>A "System Under Maintenance" page is shown</li>
                                    <li>Administrators and System Admins can still access</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span class="text-muted">
                        <i class='bx bx-info-circle me-1'></i>
                        Changes are applied immediately after saving.
                    </span>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class='bx bx-save me-1'></i> Save Configuration
                    </button>
                </div>
            </div>
        </form>

        <!-- Hidden Reset Forms -->
        <form id="resetSecurityForm" action="{{ route('admin.settings.reset') }}" method="POST" class="d-none">
            @csrf
            <input type="hidden" name="group" value="security">
        </form>
        <form id="resetForm" action="{{ route('admin.settings.reset') }}" method="POST" class="d-none">
            @csrf
            <input type="hidden" id="resetGroup" name="group" value="notifications">
        </form>
    </div>

    @push('scripts')
        <script>
            function toggleNotificationSettings(enabled) {
                const toggles = document.querySelectorAll('.notification-toggle');
                toggles.forEach(toggle => {
                    toggle.disabled = !enabled;
                    if (!enabled) {
                        toggle.parentElement.classList.add('text-muted');
                    } else {
                        toggle.parentElement.classList.remove('text-muted');
                    }
                });

                const serverSettings = document.querySelectorAll('.email-server-setting');
                serverSettings.forEach(field => {
                    field.disabled = !enabled;
                });

                const serverCard = document.getElementById('emailServerSettings');
                if (serverCard) {
                    if (!enabled) {
                        serverCard.style.opacity = '0.7';
                        serverCard.style.pointerEvents = 'none';
                    } else {
                        serverCard.style.opacity = '1';
                        serverCard.style.pointerEvents = 'auto';
                    }
                }
            }

            function togglePasswordVisibility(inputId) {
                const input = document.getElementById(inputId);
                const icon = document.getElementById(inputId + '_icon');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bx-hide');
                    icon.classList.add('bx-show');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bx-show');
                    icon.classList.add('bx-hide');
                }
            }

            function confirmMaintenanceToggle(checkbox) {
                if (checkbox.checked) {
                    if (!confirm('Enable maintenance mode? Regular users will be blocked from accessing the system.')) {
                        checkbox.checked = false;
                    }
                }
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                const masterToggle = document.getElementById('email_enabled');
                toggleNotificationSettings(masterToggle.checked);
            });
        </script>
    @endpush
@endsection
