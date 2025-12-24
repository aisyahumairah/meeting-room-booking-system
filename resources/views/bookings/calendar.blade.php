@extends('layouts.app')

@section('title', 'Booking Calendar')

@push('styles')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <style>
        /* Calendar Styling */
        #bookingCalendar {
            min-height: 700px;
        }

        .fc-event {
            cursor: pointer;
            font-size: 0.8rem;
            border-radius: 4px;
        }

        .fc-daygrid-event {
            padding: 2px 4px;
        }

        .fc-timegrid-event {
            border-radius: 4px;
        }

        .fc-event:hover {
            opacity: 0.9;
        }

        /* Legend */
        .calendar-legend {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }

        /* Event tooltip */
        .event-tooltip {
            position: absolute;
            z-index: 1050;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 280px;
            font-size: 13px;
            pointer-events: none;
            /* Prevent tooltip from blocking clicks */
        }
    </style>
@endpush

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Bookings /</span> Calendar
            </h4>
            <div class="d-flex gap-2">
                <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> New Booking
                </a>
            </div>
        </div>

        <div class="row">
            {{-- Filters Sidebar --}}
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-filter-alt me-1"></i> Filters</h6>
                    </div>
                    <div class="card-body">
                        {{-- Room Filter --}}
                        <div class="mb-3">
                            <label class="form-label">Room</label>
                            <select class="form-select form-select-sm" id="roomFilter">
                                <option value="">All Rooms</option>
                                @foreach ($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date Jump --}}
                        <div class="mb-3">
                            <label class="form-label">Jump to Date</label>
                            <input type="date" class="form-control form-control-sm" id="dateJump"
                                value="{{ date('Y-m-d') }}">
                        </div>

                        <button class="btn btn-outline-primary btn-sm w-100" id="todayBtn">
                            <i class="bx bx-calendar me-1"></i> Today
                        </button>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-palette me-1"></i> Legend</h6>
                    </div>
                    <div class="card-body">
                        <div class="calendar-legend flex-column">
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #28a745;"></span>
                                <span>Your Bookings</span>
                            </div>
                            @if ($canViewAllBookings)
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #17a2b8;"></span>
                                    <span>Others' Bookings</span>
                                </div>
                            @endif
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #6c757d;"></span>
                                <span>Completed</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Create --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-1"></i> Tips</h6>
                    </div>
                    <div class="card-body small">
                        <ul class="mb-0">
                            <li>Click an empty slot to create a booking</li>
                            <li>Click an event to view details</li>
                            <li>Use room filter to focus on one room</li>
                            <li>Switch views using the buttons above the calendar</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Calendar --}}
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div id="bookingCalendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Event Detail Tooltip (hidden by default) --}}
    <div id="eventTooltip" class="event-tooltip" style="display: none;">
        <div class="tooltip-content"></div>
    </div>

    {{-- Booking Details Modal --}}
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8" id="modalReference"></dd>

                        <dt class="col-sm-4">Room</dt>
                        <dd class="col-sm-8" id="modalRoom"></dd>

                        <dt class="col-sm-4">User</dt>
                        <dd class="col-sm-8" id="modalUser"></dd>

                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8" id="modalDate"></dd>

                        <dt class="col-sm-4">Time</dt>
                        <dd class="col-sm-8" id="modalTime"></dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8" id="modalStatus"></dd>

                        <dt class="col-sm-4">Purpose</dt>
                        <dd class="col-sm-8 text-wrap" id="modalPurpose"></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <a href="#" id="modalViewLink" class="btn btn-primary btn-sm">View Full Details</a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const calendarEl = document.getElementById('bookingCalendar');
                const roomFilter = document.getElementById('roomFilter');
                const dateJump = document.getElementById('dateJump');
                const todayBtn = document.getElementById('todayBtn');
                const tooltip = document.getElementById('eventTooltip');

                // Initialize FullCalendar
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    initialDate: new Date(),
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    slotMinTime: '08:00:00',
                    slotMaxTime: '18:00:00',
                    weekends: true,
                    height: 'auto',
                    navLinks: true,
                    selectable: true,
                    selectMirror: true,
                    nowIndicator: true,
                    eventTimeFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },
                    slotLabelFormat: {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    },

                    // Fetch events
                    events: function(info, successCallback, failureCallback) {
                        const roomId = roomFilter.value;
                        let url =
                            `{{ route('ajax.calendar.events') }}?start=${encodeURIComponent(info.startStr)}&end=${encodeURIComponent(info.endStr)}`;

                        if (roomId) {
                            url += `&room_id=${roomId}`;
                        }

                        fetch(url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.json())
                            .then(data => successCallback(data))
                            .catch(error => {
                                console.error('Failed to load events:', error);
                                failureCallback(error);
                            });
                    },

                    // Click on event - navigate to detail
                    // Click on event - show modal
                    eventClick: function(info) {
                        info.jsEvent.preventDefault();

                        // Populate modal data
                        const props = info.event.extendedProps;
                        const start = info.event.start;
                        const end = info.event.end;

                        if (!props) return;

                        document.getElementById('modalReference').textContent = props.reference || '-';
                        document.getElementById('modalRoom').textContent = props.room || '-';
                        document.getElementById('modalUser').textContent = props.user || '-';

                        // Format Date
                        const dateOptions = {
                            weekday: 'short',
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        };
                        document.getElementById('modalDate').textContent = start ? start.toLocaleDateString(
                            'en-US', dateOptions) : '-';

                        // Format Time
                        const timeOptions = {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: false
                        };
                        const startTime = start ? start.toLocaleTimeString('en-US', timeOptions) : '';
                        const endTime = end ? end.toLocaleTimeString('en-US', timeOptions) : '';
                        document.getElementById('modalTime').textContent =
                            `${startTime} - ${endTime} (${props.duration})`;

                        // Status badge
                        const statusEl = document.getElementById('modalStatus');
                        const statusColor = info.event.backgroundColor || '#6c757d';
                        statusEl.innerHTML =
                            `<span class="badge" style="background-color: ${statusColor}">${props.status.toUpperCase()}</span>`;

                        document.getElementById('modalPurpose').textContent = props.purpose || '-';

                        // View Link
                        // View Link
                        const viewLink = document.getElementById('modalViewLink');
                        // Only show the link if it's the user's own booking (or if they are admin, handled by backend sending URL)
                        // BUT user specifically requested "user should cannot view details other user bookings"
                        // The backend ONLY sends a URL if the user is allowed to view it (own booking or admin).
                        // However, to be extra safe and explicit based on the request "remove the button view full details on modal"
                        // we can check the 'isOwn' prop if we want to restricted it strictly to own bookings even for admins,
                        // OR we stick to the existing logic which already hides it if 'url' is null.
                        // The user said "user should cannot view details other user bookings".
                        // In Controller: 'url' is null if (!isOwn && !canViewAll).
                        // So if I am a regular user, url is ALREADY null for others' bookings.
                        // If the user means "EVEN IF I can view it, don't show the button", that's different.
                        // But typically "cannot view details" means "don't show the detailed page".
                        // Let's assume the user wants to be sure.
                        // The previous code `if (info.event.url)` ALREADY handles this for regular users.
                        // If the user thinks it's not working, maybe they are testing as Admin?
                        // "remove the button... on modal" implying it might be showing up when it shouldn't.

                        // Let's explicitly check props.isOwn to be safe if that's what they mean by "user".
                        // If they mean "Regular User", then checking `info.event.url` is correct because the controller sets it to null.
                        // However, let's look at the controller again.
                        // Controller: 'url' => $isOwn ? route(...) : ($canViewAll ? route(...) : null)

                        // If the user wants NO ONE to see full details of others (even admins via this modal?), or just regular users?
                        // "user should cannot view details other user bookings" -> implies regular user.

                        // I will add a check for `props.isOwn` to strictly limit it if that's the requirement, 
                        // OR trust the URL. The prompt implies the button IS showing up.
                        // If the button IS showing up for other users, it means `info.event.url` IS present.
                        // Which means the controller thinks they `canViewAll`.

                        // To strictly satisfy "user should cannot view details other user bookings", 
                        // I will update the JS to ONLY show the button if it is their own booking, 
                        // ignoring the admin privilege for a moment or strictly following "other user bookings".

                        if (props.isOwn && info.event.url) {
                            viewLink.href = info.event.url;
                            viewLink.hidden = false;
                        } else {
                            viewLink.hidden = true;
                        }

                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
                        modal.show();
                    },

                    // Hover on event - show tooltip
                    eventMouseEnter: function(info) {
                        showEventTooltip(info.event, info.jsEvent);
                    },

                    eventMouseLeave: function(info) {
                        hideEventTooltip();
                    },

                    // Click on empty slot - go to create booking
                    select: function(info) {
                        const roomId = roomFilter.value || '';
                        const date = info.startStr.split('T')[0];
                        const startTime = info.startStr.includes('T') ?
                            info.startStr.split('T')[1].substring(0, 5) :
                            '09:00';
                        const endTime = info.endStr.includes('T') ?
                            info.endStr.split('T')[1].substring(0, 5) :
                            '10:00';

                        // Navigate to create booking with pre-filled values
                        window.location.href =
                            `{{ route('bookings.create') }}?room_id=${roomId}&date=${date}&start_time=${startTime}&end_time=${endTime}`;
                    },

                    // Date click (in month view)
                    dateClick: function(info) {
                        // Switch to timeGridDay view when clicking a date in dayGridMonth view
                        // Note: dateClick triggers on day cells. select triggers on selection.
                        // If selectable is true, dateClick might conflict if not careful.
                        // But usually dateClick works for the day cels.
                        if (calendar.view.type === 'dayGridMonth') {
                            calendar.changeView('timeGridDay', info.date);
                        }
                    },

                    // View change
                    viewDidMount: function(info) {
                        hideEventTooltip();
                    },
                });

                calendar.render();

                // Room filter change
                roomFilter.addEventListener('change', function() {
                    calendar.refetchEvents();
                });

                // Date jump
                dateJump.addEventListener('change', function() {
                    calendar.gotoDate(this.value);
                });

                // Today button
                todayBtn.addEventListener('click', function() {
                    calendar.today();
                    dateJump.value = new Date().toISOString().split('T')[0];
                });

                // Event tooltip functions
                function showEventTooltip(event, jsEvent) {
                    const props = event.extendedProps;
                    const content = `
                    <div class="mb-2">
                        <strong class="text-primary">${props.room}</strong>
                        ${props.isRecurring ? '<i class="bx bx-repeat text-info ms-1" title="Recurring"></i>' : ''}
                    </div>
                    <div class="small">
                        <p class="mb-1"><i class="bx bx-time me-1"></i> ${event.start.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })} - ${event.end.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })} (${props.duration})</p>
                        <p class="mb-1"><i class="bx bx-user me-1"></i> ${props.user}</p>
                        <p class="mb-1"><i class="bx bx-info-circle me-1"></i> ${props.purpose}</p>
                        <p class="mb-0"><span class="badge bg-${props.status === 'confirmed' ? 'success' : 'secondary'}">${props.status}</span></p>
                    </div>
                    ${event.url ? '<div class="mt-2 small text-muted">Click to view details</div>' : ''}
                `;

                    tooltip.querySelector('.tooltip-content').innerHTML = content;
                    tooltip.style.display = 'block';
                    tooltip.style.left = (jsEvent.pageX + 10) + 'px';
                    tooltip.style.top = (jsEvent.pageY + 10) + 'px';
                }

                function hideEventTooltip() {
                    tooltip.style.display = 'none';
                }

                // Hide tooltip on scroll
                document.addEventListener('scroll', hideEventTooltip, true);
            });
        </script>
    @endpush
@endsection
