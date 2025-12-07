@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Account / <a href="{{ route('profile.show') }}">My Profile</a> /</span> Change
        Password
    </h4>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <h5 class="card-header">Change Password</h5>
                <div class="card-body">
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <!-- Current Password -->
                                <div class="mb-3 form-password-toggle">
                                    <label class="form-label" for="current_password">Current Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge">
                                        <input class="form-control @error('current_password') is-invalid @enderror"
                                            type="password" id="current_password" name="current_password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            required>
                                        <span class="input-group-text cursor-pointer toggle-password"><i
                                                class="bx bx-hide"></i></span>
                                        @error('current_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div class="mb-3 form-password-toggle">
                                    <label class="form-label" for="password">New Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge">
                                        <input class="form-control @error('password') is-invalid @enderror" type="password"
                                            id="password" name="password"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            required>
                                        <span class="input-group-text cursor-pointer toggle-password"><i
                                                class="bx bx-hide"></i></span>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Confirm New Password -->
                                <div class="mb-3 form-password-toggle">
                                    <label class="form-label" for="password_confirmation">Confirm New Password <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge">
                                        <input class="form-control" type="password" id="password_confirmation"
                                            name="password_confirmation"
                                            placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                            required>
                                        <span class="input-group-text cursor-pointer toggle-password"><i
                                                class="bx bx-hide"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Password Requirements -->
                                <div class="alert alert-secondary">
                                    <h6 class="alert-heading fw-bold mb-2">Password Requirements:</h6>
                                    <ul class="ps-3 mb-0">
                                        <li class="mb-1">Minimum 8 characters long</li>
                                        <li class="mb-1">At least one letter</li>
                                        <li class="mb-1">At least one number</li>
                                        <li>At least one special character (symbol)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bx bx-lock-alt me-1"></i> Change Password
                            </button>
                            <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bx-hide');
                    icon.classList.add('bx-show');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bx-show');
                    icon.classList.add('bx-hide');
                }
            });
        });
    </script>
@endpush
