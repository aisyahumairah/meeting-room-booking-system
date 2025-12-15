<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Booking (Admin)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to cancel booking <strong id="cancelRef"></strong>.</p>

                    <div id="seriesWarning" class="alert alert-warning" style="display: none;">
                        <i class="bx bx-repeat me-1"></i>
                        This will cancel the <strong>entire series</strong> (<span id="seriesCount">0</span> bookings).
                    </div>

                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        The booking owner will be notified of this cancellation.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admin Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" rows="3" required maxlength="500"
                            placeholder="Reason for administrative cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn btn-danger">Cancel Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>