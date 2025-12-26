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
                                        <span class="input-group-text cursor-pointer toggle-password"
                                            data-target="current_password"><i class="bx bx-hide"></i></span>
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
                                        <span class="input-group-text cursor-pointer toggle-password"
                                            data-target="password"><i class="bx bx-hide"></i></span>
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
                                        <span class="input-group-text cursor-pointer toggle-password"
                                            data-target="password_confirmation"><i class="bx bx-hide"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <!-- Password Validation Criteria -->
                                <div id="password-requirements" class="p-3 border rounded-3 bg-label-secondary shadow-sm">
                                    <h6 class="mb-3 fw-bold border-bottom pb-2 text-primary">
                                        <i class="bx bx-shield-quarter me-2"></i>Password Security Checklist
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <li id="req-length"
                                            class="text-muted mb-2 d-flex align-items-center transition-all">
                                            <i class="bx bx-error-circle me-3 fs-5"></i>
                                            <span>Minimum 8 characters</span>
                                        </li>
                                        <li id="req-capital"
                                            class="text-muted mb-2 d-flex align-items-center transition-all">
                                            <i class="bx bx-error-circle me-3 fs-5"></i>
                                            <span>At least one capital letter</span>
                                        </li>
                                        <li id="req-number"
                                            class="text-muted mb-2 d-flex align-items-center transition-all">
                                            <i class="bx bx-error-circle me-3 fs-5"></i>
                                            <span>At least one number</span>
                                        </li>
                                        <li id="req-symbol" class="text-muted d-flex align-items-center transition-all">
                                            <i class="bx bx-error-circle me-3 fs-5"></i>
                                            <span>At least one special character</span>
                                        </li>
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
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');

            const criteria = {
                length: {
                    regex: /.{8,}/,
                    element: document.getElementById('req-length')
                },
                capital: {
                    regex: /[A-Z]/,
                    element: document.getElementById('req-capital')
                },
                number: {
                    regex: /[0-9]/,
                    element: document.getElementById('req-number')
                },
                symbol: {
                    regex: /[^A-Za-z0-9]/,
                    element: document.getElementById('req-symbol')
                }
            };

            function updateRequirement(id, isValid) {
                const item = criteria[id].element;
                if (!item) return;
                const icon = item.querySelector('i');

                if (isValid) {
                    item.classList.remove('text-muted', 'text-danger');
                    item.classList.add('text-success', 'fw-semibold');
                    icon.classList.replace('bx-error-circle', 'bx-check-circle');
                    icon.classList.replace('bx-x-circle', 'bx-check-circle');
                } else if (passwordInput.value.length > 0) {
                    item.classList.remove('text-muted', 'text-success', 'fw-semibold');
                    item.classList.add('text-danger');
                    icon.classList.replace('bx-error-circle', 'bx-x-circle');
                    icon.classList.replace('bx-check-circle', 'bx-x-circle');
                } else {
                    item.classList.remove('text-danger', 'text-success', 'fw-semibold');
                    item.classList.add('text-muted');
                    icon.classList.replace('bx-x-circle', 'bx-error-circle');
                    icon.classList.replace('bx-check-circle', 'bx-error-circle');
                }
            }

            passwordInput.addEventListener('input', function() {
                const value = this.value;
                Object.keys(criteria).forEach(key => {
                    updateRequirement(key, criteria[key].regex.test(value));
                });
            });
        });
    </script>
@endpush
