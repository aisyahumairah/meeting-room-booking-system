<div class="modal fade" id="sessionExpiredModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="bx bx-time-five me-2"></i>
                    Session Expired
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="bx bx-lock-alt text-warning" style="font-size: 4rem;"></i>
                <h5 class="mt-3">Your session has expired</h5>
                <p class="text-muted">
                    For security reasons, your session has expired due to inactivity.
                    <br>
                    Please log in again to continue.
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <i class="bx bx-log-in me-1"></i> Log In Again
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Show modal on 401/419 AJAX responses
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined') {
            $(document).ajaxError(function(event, jqxhr) {
                if (jqxhr.status === 401 || jqxhr.status === 419) {
                    var modal = new bootstrap.Modal(document.getElementById('sessionExpiredModal'));
                    modal.show();
                }
            });
        }
    });
</script>
