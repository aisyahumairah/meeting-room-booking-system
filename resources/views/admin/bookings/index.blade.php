@extends('layouts.app')

@section('title', 'All Bookings')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Admin /</span> All Bookings
            </h4>
            <div>
                {{-- Export buttons will be added in Phase 4 --}}
                <span class="text-muted small me-2">Export: Coming in Phase 4</span>

                <form action="{{ route('admin.bookings.complete-expired') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm"
                        onclick="return confirm('Run completion check now?')">
                        <i class="bx bx-check-double me-1"></i> Complete Expired
                    </button>
                </form>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row mb-4">
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="bx bx-calendar"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Total Bookings</small>
                                <h5 class="mb-0">{{ number_format($stats['total']) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-calendar-check"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Today's Bookings</small>
                                <h5 class="mb-0">{{ $stats['today'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="bx bx-calendar-plus"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Upcoming</small>
                                <h5 class="mb-0">{{ $stats['upcoming'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="bx bx-calendar-x"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Cancelled (This Month)</small>
                                <h5 class="mb-0">{{ $stats['cancelled_this_month'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bookings Table --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                {{-- Quick Filters --}}
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.bookings.index', ['filter' => 'today']) }}"
                        class="btn btn-outline-primary btn-sm {{ request('filter') === 'today' ? 'active' : '' }}">
                        Today
                    </a>
                    <a href="{{ route('admin.bookings.index', ['filter' => 'upcoming']) }}"
                        class="btn btn-outline-success btn-sm {{ request('filter') === 'upcoming' ? 'active' : '' }}">
                        Upcoming
                    </a>
                    <a href="{{ route('admin.bookings.index', ['filter' => 'past']) }}"
                        class="btn btn-outline-secondary btn-sm {{ request('filter') === 'past' ? 'active' : '' }}">
                        Past
                    </a>
                    <a href="{{ route('admin.bookings.index') }}"
                        class="btn btn-outline-dark btn-sm {{ !request('filter') ? 'active' : '' }}">
                        All
                    </a>
                </div>

                {{-- Search --}}
                <form action="{{ route('admin.bookings.index') }}" method="GET" class="d-flex gap-2">
                    <input type="text" class="form-control form-control-sm" name="search"
                        placeholder="Search reference or user..." value="{{ request('search') }}" style="width: 200px;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bx bx-search"></i>
                    </button>
                </form>
            </div>

            {{-- Advanced Filters --}}
            <div class="card-body border-bottom">
                <form action="{{ route('admin.bookings.index') }}" method="GET" class="row g-3" id="filterForm">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed
                            </option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled
                            </option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Room</label>
                        <select class="form-select form-select-sm" name="room_id">
                            <option value="">All Rooms</option>
                            @foreach ($rooms as $room)
                                <option value="{{ $room->id }}"
                                    {{ request('room_id') == $room->id ? 'selected' : '' }}>
                                    {{ $room->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">User</label>
                        <select class="form-select form-select-sm" name="user_id">
                            <option value="">All Users</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to"
                            value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <table class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Room</th>
                            <th>Date & Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookings as $booking)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.bookings.show', $booking) }}">
                                        {{ $booking->reference_number }}
                                    </a>
                                    @if ($booking->is_recurring)
                                        <i class="bx bx-repeat text-info" title="Recurring"></i>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $booking->user->name }}</div>
                                    <small class="text-muted">{{ $booking->user->email }}</small>
                                </td>
                                <td>
                                    <div>{{ $booking->room->name }}</div>
                                    <small class="text-muted">{{ $booking->room->floor_location }}</small>
                                </td>
                                <td>
                                    <div>{{ $booking->booking_date->format('D, M d, Y') }}</div>
                                    <small class="text-muted">{{ $booking->time_range }}
                                        ({{ $booking->duration }})
                                    </small>
                                </td>
                                <td>
                                    <span title="{{ $booking->purpose }}">
                                        {{ Str::limit($booking->purpose, 30) }}
                                    </span>
                                </td>
                                <td>{!! $booking->status_badge !!}</td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('admin.bookings.show', $booking) }}">
                                                <i class="bx bx-show me-1"></i> View Details
                                            </a>
                                            @if ($booking->status !== 'completed')
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.bookings.edit', $booking) }}">
                                                    <i class="bx bx-edit me-1"></i> Edit
                                                </a>
                                            @endif
                                            @if ($booking->status === 'confirmed')
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#"
                                                    onclick="confirmCancel(
                                                                           {{ $booking->id }}, 
                                                                           '{{ $booking->reference_number }}',
                                                                           {{ $booking->is_recurring ? 'true' : 'false' }},
                                                                           {{ $booking->is_recurring ? $booking->series->bookings()->where('status', 'confirmed')->count() : 0 }}
                                                                       )">
                                                    <i class="bx bx-x me-1"></i> Cancel
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bx bx-calendar-x bx-lg mb-2"></i>
                                        <p class="mb-0">No bookings found matching your criteria</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            {{-- @if ($bookings->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of
                            {{ $bookings->total() }}
                            bookings
                        </small>
                        {{ $bookings->links() }}
                    </div>
                </div>
            @endif --}}
        </div>
    </div>

    {{-- Cancel Modal --}}
    @include('admin.bookings.partials.cancel-modal')

    @push('scripts')
        <script>
            function confirmCancel(bookingId, reference, isRecurring, seriesCount) {
                document.getElementById('cancelRef').textContent = reference;
                document.getElementById('cancelForm').action = `{{ url('admin/bookings') }}/${bookingId}`;

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
    @endpush
@endsection
