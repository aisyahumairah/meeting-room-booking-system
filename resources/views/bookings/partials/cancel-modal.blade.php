<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel booking <strong id="cancelRef"></strong>?</p>
                    
                    {{-- Series warning (shown dynamically) --}}
                    <div id="seriesWarning" class="alert alert-warning" style="display: none;">
                        <i class="bx bx-repeat me-1"></i>
                        <strong>Recurring Booking:</strong> This will cancel the <strong>entire series</strong> 
                        (<span id="seriesCount">0</span> bookings).
                    </div>

                    <div class="alert alert-danger">
                        <i class="bx bx-error-circle me-1"></i>
                        <strong>This action cannot be undone.</strong> The room will become available for others to book.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" rows="3" 
                                  required maxlength="500" 
                                  placeholder="Please provide a reason for cancellation..."></textarea>
                        <div class="form-text">Maximum 500 characters. This will be logged for records.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-arrow-back me-1"></i> Go Back
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-x me-1"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmCancel(bookingId, reference, isRecurring = false, seriesCount = 0) {
    document.getElementById('cancelRef').textContent = reference;
    document.getElementById('cancelForm').action = `/my-bookings/${bookingId}`;
    
    // Show/hide series warning
    const seriesWarning = document.getElementById('seriesWarning');
    if (isRecurring && seriesCount > 0) {
        document.getElementById('seriesCount').textContent = seriesCount;
        seriesWarning.style.display = 'block';
    } else {
        seriesWarning.style.display = 'none';
    }
    
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>