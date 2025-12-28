@extends('layouts.app')

@section('title', 'Room Management')

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Room Management</h4>
                    <p class="text-muted mb-0">Manage meeting rooms and their amenities</p>
                </div>
                <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Add New Room
                </a>
            </div>

            {{-- Status Summary Cards --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-check-circle"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Active Rooms</small>
                                <h5 class="mb-0">{{ $statusCounts['active'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-secondary">
                                    <i class="bx bx-x-circle"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Inactive Rooms</small>
                                <h5 class="mb-0">{{ $statusCounts['inactive'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="bx bx-wrench"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Under Maintenance</small>
                                <h5 class="mb-0">{{ $statusCounts['under_maintenance'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters & Search --}}
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ route('admin.rooms.index') }}" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-search"></i></span>
                                <input type="text" class="form-control" name="search"
                                    placeholder="Search by room name..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                                <option value="under_maintenance"
                                    {{ request('status') === 'under_maintenance' ? 'selected' : '' }}>Under Maintenance
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Rooms Table --}}
            <div class="card">
                <div class="card-body">
                    @if ($rooms->total() > 0)
                        <table class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th>
                                        <a href="{{ route('admin.rooms.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort') === 'name' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                            class="text-body d-flex align-items-center">
                                            Room Name
                                            @if (request('sort') === 'name')
                                                <i
                                                    class="bx bx-chevron-{{ request('direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('admin.rooms.index', array_merge(request()->query(), ['sort' => 'capacity', 'direction' => request('sort') === 'capacity' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                            class="text-body d-flex align-items-center">
                                            Capacity
                                            @if (request('sort') === 'capacity')
                                                <i
                                                    class="bx bx-chevron-{{ request('direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>
                                        <a href="{{ route('admin.rooms.index', array_merge(request()->query(), ['sort' => 'floor_location', 'direction' => request('sort') === 'floor_location' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}"
                                            class="text-body d-flex align-items-center">
                                            Floor/Location
                                            @if (request('sort') === 'floor_location')
                                                <i
                                                    class="bx bx-chevron-{{ request('direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Status</th>
                                    <th>Amenities</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rooms as $room)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="{{ $room->primary_image }}" alt="{{ $room->name }}"
                                                    class="rounded me-3"
                                                    style="width: 45px; height: 45px; object-fit: cover;">
                                                <div>
                                                    <strong>{{ $room->name }}</strong>
                                                    @if ($room->deleted_at)
                                                        <span class="badge bg-danger ms-1">Deleted</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="bx bx-user me-1 text-muted"></i>
                                            {{ $room->capacity }} pax
                                        </td>
                                        <td>{{ $room->floor_location }}</td>
                                        <td>{!! $room->status_badge !!}</td>
                                        <td>
                                            @if ($room->amenities->count() > 0)
                                                <div class="d-flex gap-1 flex-wrap" style="max-width: 200px;">
                                                    @foreach ($room->amenities->take(3) as $amenity)
                                                        <span class="badge bg-label-primary" title="{{ $amenity->name }}">
                                                            {!! $amenity->icon_html !!}
                                                        </span>
                                                    @endforeach
                                                    @if ($room->amenities->count() > 3)
                                                        <span
                                                            class="badge bg-label-secondary">+{{ $room->amenities->count() - 3 }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">None</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-outline-primary dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('admin.rooms.edit', $room) }}">
                                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                                    </a>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                                        data-bs-target="#statusModal{{ $room->id }}">
                                                        <i class="bx bx-refresh me-1"></i> Change Status
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal{{ $room->id }}">
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5">
                            <i class="bx bx-building bx-lg text-muted mb-2"></i>
                            <p class="mb-0 text-muted">No rooms found matching your criteria</p>
                            @if (request()->hasAny(['search', 'status']))
                                <a href="{{ route('admin.rooms.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    Clear Filters
                                </a>
                            @else
                                <a href="{{ route('admin.rooms.create') }}" class="btn btn-sm btn-primary mt-2">
                                    <i class="bx bx-plus me-1"></i> Add First Room
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Pagination --}}
                @if ($rooms->hasPages())
                    <div class="card-footer d-flex justify-content-center">
                        {{ $rooms->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modals --}}
    @foreach ($rooms as $room)
        {{-- Status Modal --}}
        <div class="modal fade" id="statusModal{{ $room->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.rooms.status', $room) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Change Room Status</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Update status for <strong>{{ $room->name }}</strong></p>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="statusSelect{{ $room->id }}"
                                    onchange="toggleMaintenanceFields({{ $room->id }})">
                                    <option value="active" {{ $room->status === 'active' ? 'selected' : '' }}>Active
                                    </option>
                                    <option value="inactive" {{ $room->status === 'inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                    <option value="under_maintenance"
                                        {{ $room->status === 'under_maintenance' ? 'selected' : '' }}>
                                        Under Maintenance</option>
                                </select>
                            </div>

                            <div id="maintenanceFields{{ $room->id }}"
                                class="{{ $room->status !== 'under_maintenance' ? 'd-none' : '' }}">
                                <div class="mb-3">
                                    <label class="form-label">Maintenance Start</label>
                                    <input type="datetime-local" class="form-control" name="maintenance_start">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Maintenance End</label>
                                    <input type="datetime-local" class="form-control" name="maintenance_end">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason (optional)</label>
                                    <textarea class="form-control" name="maintenance_reason" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Delete Modal --}}
        <div class="modal fade" id="deleteModal{{ $room->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Room</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <i class="bx bx-error-circle text-danger" style="font-size: 4rem;"></i>
                            </div>
                            <p class="text-center">
                                Are you sure you want to delete
                                <strong>{{ $room->name }}</strong>?
                            </p>
                            <p class="text-center text-muted small">
                                This action cannot be undone. Rooms with bookings cannot be deleted.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete Room</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    <script>
        function toggleMaintenanceFields(roomId) {
            const select = document.getElementById('statusSelect' + roomId);
            const fields = document.getElementById('maintenanceFields' + roomId);

            if (select.value === 'under_maintenance') {
                fields.classList.remove('d-none');
            } else {
                fields.classList.add('d-none');
            }
        }
    </script>
@endpush
