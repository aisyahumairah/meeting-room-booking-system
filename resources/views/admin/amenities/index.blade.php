@extends('layouts.app')

@section('title', 'Amenity Management')

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Amenity Management</h4>
                    <p class="text-muted mb-0">Manage room amenities and equipment</p>
                </div>
                <a href="{{ route('admin.amenities.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Add New Amenity
                </a>
            </div>

            {{-- Status Summary Cards --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="bx bx-check-circle"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Active Amenities</small>
                                <h5 class="mb-0">{{ $statusCounts['active'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-secondary">
                                    <i class="bx bx-x-circle"></i>
                                </span>
                            </div>
                            <div>
                                <small class="text-muted d-block">Inactive Amenities</small>
                                <h5 class="mb-0">{{ $statusCounts['inactive'] }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters & Search --}}
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ route('admin.amenities.index') }}" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-search"></i></span>
                                <input type="text" class="form-control" name="search"
                                    placeholder="Search by amenity name..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active
                                </option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Amenities Table --}}
            <div class="card">
                @if ($amenities->total() > 0)
                    <div class="card-body">
                        <table class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Rooms Using</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($amenities as $amenity)
                                    <tr class="{{ !$amenity->is_active ? 'opacity-50' : '' }}">
                                        <td>
                                            <i class='bx {{ $amenity->icon }} bx-md'></i>
                                        </td>
                                        <td>
                                            <strong>{{ $amenity->name }}</strong>
                                            @if (!$amenity->is_active)
                                                <span class="text-muted small">(Inactive)</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">
                                            {{ $amenity->description ?? '—' }}
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.amenities.update-status', $amenity) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit"
                                                    class="badge bg-label-{{ $amenity->is_active ? 'success' : 'secondary' }} border-0"
                                                    title="Click to toggle status" style="cursor: pointer;">
                                                    {{ $amenity->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-info">
                                                {{ $amenity->rooms_count }} room(s)
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-outline-primary dropdown-toggle hide-arrow"
                                                    data-bs-toggle="dropdown">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.amenities.show', $amenity) }}">
                                                        <i class="bx bx-show me-1"></i> View Details
                                                    </a>
                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.amenities.edit', $amenity) }}">
                                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                                    </a>
                                                    <form action="{{ route('admin.amenities.update-status', $amenity) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="bx bx-refresh me-1"></i>
                                                            {{ $amenity->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal{{ $amenity->id }}"
                                                        @if ($amenity->rooms_count > 0) onclick="return false;" 
                                                        style="opacity: 0.5; cursor: not-allowed;"
                                                        title="Cannot delete: In use by rooms" @endif>
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="card-body text-center py-5">
                        <i class="bx bx-wrench bx-lg text-muted mb-2"></i>
                        <p class="mb-0 text-muted">No amenities found matching your criteria.</p>
                        @if (request()->hasAny(['search', 'status']))
                            <a href="{{ route('admin.amenities.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                Clear Filters
                            </a>
                        @else
                            <a href="{{ route('admin.amenities.create') }}" class="btn btn-sm btn-primary mt-2">
                                <i class="bx bx-plus me-1"></i> Add First Amenity
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Pagination --}}
                @if ($amenities->hasPages())
                    <div class="card-footer d-flex justify-content-center">
                        {{ $amenities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Delete Modals --}}
    @foreach ($amenities as $amenity)
        <div class="modal fade" id="deleteModal{{ $amenity->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.amenities.destroy', $amenity) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-header">
                            <h5 class="modal-title">Delete Amenity</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <i class="bx bx-error-circle text-danger" style="font-size: 4rem;"></i>
                            </div>
                            <p class="text-center">
                                Are you sure you want to delete
                                <strong>{{ $amenity->name }}</strong>?
                            </p>
                            @if ($amenity->rooms_count > 0)
                                <div class="alert alert-warning">
                                    <i class="bx bx-error me-1"></i>
                                    This amenity is used by {{ $amenity->rooms_count }} room(s) and cannot be deleted.
                                </div>
                            @else
                                <p class="text-center text-muted small">
                                    This action cannot be undone.
                                </p>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger"
                                @if ($amenity->rooms_count > 0) disabled @endif>
                                Delete Amenity
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection
