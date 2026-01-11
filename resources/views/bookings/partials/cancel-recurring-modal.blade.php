<div class="modal fade" id="cancelRecurringModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="cancelRecurringForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Recurring Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>You are cancelling booking <strong id="cancelRecurringRef"></strong>.</p>
                    <p>This booking is part of a recurring series.</p>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Cancellation Options</label>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="cancel_mode" id="cancelSingle"
                                value="single" checked>
                            <label class="form-check-label" for="cancelSingle">
                                <strong>Cancel This Date Only</strong>
                                <div class="text-muted small">
                                    Remove only this specific occurrence (<span id="cancelDate"></span>). The rest of
                                    the series remains active.
                                </div>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="cancel_mode" id="cancelAll"
                                value="all">
                            <label class="form-check-label" for="cancelAll">
                                <strong>Cancel All Bookings In Series</strong>
                                <div class="text-muted small">
                                    Cancel all confirmed bookings in this series. Past completed bookings will be
                                    preserved.
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="cancellation_reason" id="cancelRecurringReason" rows="3" required
                            maxlength="500" placeholder="Please provide a reason for cancellation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Go Back</button>
                    <button type="submit" class="btn btn-danger" id="cancelRecurringSubmit" disabled>Confirm
                        Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function confirmCancelRecurring(bookingId, reference, date, bookingUrl) {
        document.getElementById('cancelRecurringRef').textContent = reference;
        document.getElementById('cancelDate').textContent = date;

        // Reset form state
        const form = document.getElementById('cancelRecurringForm');
        form.reset();

        // Disable submit button initially
        const submitBtn = document.getElementById('cancelRecurringSubmit');
        submitBtn.disabled = true;

        // Set the form action URL
        const url = bookingUrl || `/my-bookings/${bookingId}`;
        form.action = url;

        new bootstrap.Modal(document.getElementById('cancelRecurringModal')).show();
    }

    // Enable/disable submit button based on reason input
    document.addEventListener('DOMContentLoaded', function() {
        const reasonInput = document.getElementById('cancelRecurringReason');
        const submitBtn = document.getElementById('cancelRecurringSubmit');

        if (reasonInput && submitBtn) {
            reasonInput.addEventListener('input', function() {
                submitBtn.disabled = this.value.trim().length === 0;
            });
        }
    });
</script>
