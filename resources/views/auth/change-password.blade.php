@extends('layouts.auth')

@section('title', 'Change Password')

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <div class="card px-sm-6 px-0">
                    <div class="card-body">
                        <div class="app-brand justify-content-center">
                            <span class="app-brand-text demo text-heading fw-bold">MRBS</span>
                        </div>

                        <h4 class="mb-1">Change Your Password 🔐</h4>
                        <p class="mb-6">For security, please set a new password before continuing.</p>

                        <form action="{{ route('change.update') }}" method="POST">
                            @csrf

                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="current_password">Current Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="current_password"
                                        class="form-control @error('current_password') is-invalid @enderror"
                                        name="current_password" required />
                                    <span class="input-group-text cursor-pointer toggle-password"
                                        data-target="current_password">
                                        <i class="icon-base bx bx-hide"></i>
                                    </span>
                                </div>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="password">New Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        required />
                                    <span class="input-group-text cursor-pointer toggle-password" data-target="password">
                                        <i class="icon-base bx bx-hide"></i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Min 8 chars with letters, numbers, and symbols</small>

                                <!-- Password Validation Criteria -->
                                <div id="password-requirements"
                                    class="mt-4 mb-2 p-3 border rounded-3 bg-label-secondary shadow-sm"
                                    style="display: none;">
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

                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="password_confirmation">Confirm Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password_confirmation" class="form-control"
                                        name="password_confirmation" required />
                                    <span class="input-group-text cursor-pointer toggle-password"
                                        data-target="password_confirmation">
                                        <i class="icon-base bx bx-hide"></i>
                                    </span>
                                </div>
                            </div>

                            <button class="btn btn-primary d-grid w-100" type="submit">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const requirementsContainer = document.getElementById('password-requirements');

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

            passwordInput.addEventListener('focus', () => {
                requirementsContainer.style.display = 'block';
            });

            passwordInput.addEventListener('input', function() {
                const value = this.value;

                if (value.length > 0) {
                    requirementsContainer.style.display = 'block';
                }

                Object.keys(criteria).forEach(key => {
                    updateRequirement(key, criteria[key].regex.test(value));
                });
            });
        });
    </script>
@endpush
