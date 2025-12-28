@extends('layouts.app')

@section('title', 'Amenity Details: ' . $amenity->name)

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Amenity Details</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.amenities.index') }}">Amenity Management</a>
                            </li>
                            <li class="breadcrumb-item active">{{ $amenity->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.amenities.edit', $amenity) }}" class="btn btn-primary">
                        <i class="bx bx-edit-alt me-1"></i> Edit Amenity
                    </a>
                    <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row">
                {{-- Amenity Overview --}}
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="avatar avatar-xl mx-auto mb-3 bg-label-primary rounded p-2">
                                    <i class="bx {{ $amenity->icon }} fs-1"></i>
                                </div>
                                <h5 class="mb-1">{{ $amenity->name }}</h5>
                                <form action="{{ route('admin.amenities.update-status', $amenity) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                        class="badge bg-label-{{ $amenity->is_active ? 'success' : 'secondary' }} border-0 cursor-pointer">
                                        {{ $amenity->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </div>

                            <hr class="my-3">

                            <div class="info-container">
                                <ul class="list-unstyled">
                                    <li class="mb-3">
                                        <span class="fw-bold me-2">Icon Class:</span>
                                        <code>{{ $amenity->icon }}</code>
                                    </li>
                                    <li class="mb-3">
                                        <span class="fw-bold me-2">Description:</span>
                                        <p class="text-muted mt-1">
                                            {{ $amenity->description ?? 'No description provided.' }}</p>
                                    </li>
                                    <li class="mb-0">
                                        <span class="fw-bold me-2">Total Rooms:</span>
                                        <span class="badge bg-label-info">{{ $amenity->rooms->count() }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rooms Using This Amenity --}}
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Rooms Using This Amenity</h5>
                        </div>
                        @if ($amenity->rooms->count() > 0)
                            <div class="table-responsive text-nowrap">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Room Name</th>
                                            <th>Capacity</th>
                                            <th>Floor/Location</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table-border-bottom-0">
                                        @foreach ($amenity->rooms as $room)
                                            <tr>
                                                <td>
                                                    <strong>{{ $room->name }}</strong>
                                                </td>
                                                <td>{{ $room->capacity }} pax</td>
                                                <td>{{ $room->floor_location }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-label-{{ $room->status === 'active' ? 'success' : ($room->status === 'inactive' ? 'danger' : 'warning') }}">
                                                        {{ ucwords(str_replace('_', ' ', $room->status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.rooms.edit', $room) }}"
                                                        class="btn btn-sm btn-icon btn-outline-primary">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="card-body text-center py-5">
                                <i class="bx bx-building fs-3 text-muted mb-2"></i>
                                <p class="mb-0 text-muted">No rooms are currently assigned this amenity.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
