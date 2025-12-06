/**
 * Interactions.js
 * Handles mock interactions, toasts, and simple UI logic for the MRBS Mockup.
 */

'use strict';

// Toast Notification System
function showToast(message, type = 'primary') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast-custom ${type}`;

    let icon = 'bx-bell';
    if (type === 'success') icon = 'bx-check-circle';
    if (type === 'error') icon = 'bx-x-circle';
    if (type === 'warning') icon = 'bx-error';
    if (type === 'info') icon = 'bx-info-circle';

    toast.innerHTML = `
        <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center">
                <i class="bx ${icon} me-2 fs-4"></i>
                <div class="fw-semibold">${message}</div>
            </div>
            <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;

    container.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.5s ease-out forwards';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function () {

    // 1. Handle "Save" / "Create" / "Submit" buttons
    const saveButtons = document.querySelectorAll('button[type="submit"], .btn-primary, .btn-success');
    saveButtons.forEach(btn => {
        // Exclude dropdown toggles and pure links
        if (!btn.classList.contains('dropdown-toggle') && !btn.closest('.dropdown-menu')) {
            btn.addEventListener('click', function (e) {
                // If it's a form submit, we might want to let it happen or prevent it for demo
                // For this mockup, if it's a link, let it go. If it's a button, show toast.
                if (this.tagName === 'BUTTON') {
                    // Check text content to guess action
                    const text = this.innerText.toLowerCase();
                    if (text.includes('save') || text.includes('create') || text.includes('update') || text.includes('submit')) {
                        showToast('Changes saved successfully!', 'success');
                    } else if (text.includes('book') || text.includes('confirm')) {
                        showToast('Booking request submitted!', 'success');
                    } else if (text.includes('approve')) {
                        showToast('Request approved.', 'success');
                    }
                }
            });
        }
    });

    // 2. Handle "Delete" / "Reject" / "Cancel" buttons
    const dangerButtons = document.querySelectorAll('.btn-danger, .btn-outline-danger');
    dangerButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            const text = this.innerText.toLowerCase();
            if (text.includes('delete') || text.includes('reject') || text.includes('cancel')) {
                if (!confirm('Are you sure you want to proceed with this action?')) {
                    e.preventDefault();
                    e.stopPropagation();
                } else {
                    showToast('Action completed.', 'error');
                }
            }
        });
    });

    // 3. Handle "Copy" buttons (if any)
    const copyButtons = document.querySelectorAll('.btn-copy');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            showToast('Copied to clipboard!', 'info');
        });
    });

    // 4. Simulate "Loading" state on buttons
    const loadableButtons = document.querySelectorAll('.btn-loadable');
    loadableButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Loading...';
            this.disabled = true;
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 1500);
        });
    });

    // 5. Active Menu Link Highlighting (Simple Client-side check)
    // This is a backup in case server-side rendering isn't used (which it isn't here).
    // The layout.html usually has 'active' class hardcoded, but this helps if we navigate.
    const currentPath = window.location.pathname.split('/').pop();
    if (currentPath) {
        const menuLinks = document.querySelectorAll('.menu-link');
        menuLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                // Add active class to parent li
                link.parentElement.classList.add('active');
                // Open parent submenu if exists
                const parentSub = link.closest('.menu-sub');
                if (parentSub) {
                    parentSub.parentElement.classList.add('active', 'open');
                }
            }
        });
    }
});
