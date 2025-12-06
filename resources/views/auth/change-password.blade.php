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

                        <form action="{{ route('password.change') }}" method="POST">
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
        document.querySelectorAll('.toggle-password').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const input = document.getElementById(this.getAttribute('data-target'));
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bx-hide', 'bx-show');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bx-show', 'bx-hide');
                }
            });
        });
    </script>
@endpush
