{{-- Success Toast --}}
@if (session('success'))
    <div class="bs-toast toast fade show bg-success position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive"
        aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header">
            <i class="bx bx-check me-2 text-success"></i>
            <span class="me-auto fw-semibold">Success</span>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body text-white">
            {{ session('success') }}
        </div>
    </div>
@endif

{{-- Error Toast --}}
@if (session('error'))
    <div class="bs-toast toast fade show bg-danger position-fixed bottom-0 end-0 m-3" role="alert"
        aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header">
            <i class="bx bx-error me-2 text-danger"></i>
            <span class="me-auto fw-semibold">Error</span>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body text-white">
            {{ session('error') }}
        </div>
    </div>
@endif

{{-- Warning Toast --}}
@if (session('warning'))
    <div class="bs-toast toast fade show bg-warning position-fixed bottom-0 end-0 m-3" role="alert"
        aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header">
            <i class="bx bx-error-circle me-2 text-warning"></i>
            <span class="me-auto fw-semibold">Warning</span>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            {{ session('warning') }}
        </div>
    </div>
@endif

{{-- Info Toast --}}
@if (session('info'))
    <div class="bs-toast toast fade show bg-info position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive"
        aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header">
            <i class="bx bx-info-circle me-2 text-info"></i>
            <span class="me-auto fw-semibold">Info</span>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body text-white">
            {{ session('info') }}
        </div>
    </div>
@endif

{{-- Initialize toasts with auto-hide --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toastElList = document.querySelectorAll('.toast');
            toastElList.forEach(function(toastEl) {
                const toast = new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 5000
                });
                // Toast is already shown via 'show' class, but we need to initialize for autohide
            });
        });
    </script>
@endpush
