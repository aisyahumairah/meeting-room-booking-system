@extends('layouts.app')

@section('title', 'Edit Room - ' . $room->name)

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-icon btn-outline-secondary me-3">
                        <i class="bx bx-arrow-back"></i>
                    </a>
                    <div>
                        <h4 class="mb-1">Edit Room</h4>
                        <p class="text-muted mb-0">{{ $room->name }}</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    {!! $room->status_badge !!}
                </div>
            </div>

            <form action="{{ route('admin.rooms.update', $room) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                        <div class="d-flex">
                            <i class="bx bx-error-circle me-2 icon-xs"></i>
                            <div>
                                <h6 class="alert-heading mb-1">Please correct the following errors:</h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="row">
                    {{-- Main Form --}}
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Room Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="name">Room Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $room->name) }}"
                                            placeholder="e.g., Conference Room A" required maxlength="50">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="capacity">Capacity <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number"
                                                class="form-control @error('capacity') is-invalid @enderror" id="capacity"
                                                name="capacity" value="{{ old('capacity', $room->capacity) }}"
                                                min="1" max="500" required>
                                            <span class="input-group-text">pax</span>
                                        </div>
                                        @error('capacity')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="floor_location">Floor/Location <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('floor_location') is-invalid @enderror"
                                            id="floor_location" name="floor_location"
                                            value="{{ old('floor_location', $room->floor_location) }}"
                                            placeholder="e.g., Level 5" required maxlength="100">
                                        @error('floor_location')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="description">Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                            rows="3" placeholder="Optional description of the room..." maxlength="500">{{ old('description', $room->description) }}</textarea>
                                        <small class="text-muted">Max 500 characters</small>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Current Images --}}
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Current Photos</h5>
                                <span class="badge bg-label-primary">{{ $room->images->count() }}/5</span>
                            </div>
                            <div class="card-body">
                                @if ($room->images->count() > 0)
                                    <div class="row g-3">
                                        @foreach ($room->images as $image)
                                            <div class="col-6 col-md-4">
                                                <div class="position-relative border rounded overflow-hidden">
                                                    <img src="{{ $image->url }}" alt="Room Image" class="img-fluid"
                                                        style="width: 100%; height: 120px; object-fit: cover;">

                                                    @if ($image->is_primary)
                                                        <span
                                                            class="badge bg-primary position-absolute top-0 start-0 m-2">Primary</span>
                                                    @endif

                                                    <div
                                                        class="position-absolute bottom-0 start-0 end-0 p-2 bg-dark bg-opacity-50">
                                                        <div class="d-flex justify-content-between">
                                                            @if (!$image->is_primary)
                                                                <button type="button" class="btn btn-sm btn-light"
                                                                    title="Set as primary"
                                                                    onclick="submitImageAction('{{ route('admin.rooms.images.primary', [$room, $image->id]) }}', 'POST')">
                                                                    <i class="bx bx-star"></i>
                                                                </button>
                                                            @else
                                                                <span></span>
                                                            @endif
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                title="Delete image"
                                                                onclick="if(confirm('Delete this image?')) submitImageAction('{{ route('admin.rooms.images.destroy', [$room, $image->id]) }}', 'DELETE')">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-4 text-muted">
                                        <i class="bx bx-image bx-lg mb-2"></i>
                                        <p class="mb-0">No photos uploaded yet</p>
                                    </div>
                                @endif

                                {{-- Upload More --}}
                                @if ($room->images->count() < 5)
                                    <hr class="my-4">
                                    <div class="mb-3">
                                        <label class="form-label">Add More Photos</label>
                                        <input type="file"
                                            class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                                            id="images" name="images[]" multiple accept="image/jpeg,image/png">
                                        <small class="text-muted">You can add {{ 5 - $room->images->count() }} more
                                            image(s)</small>
                                        @error('images')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error('images.*')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Maintenance Schedules --}}
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Upcoming Maintenance</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#addMaintenanceModal">
                                    <i class="bx bx-plus me-1"></i> Schedule
                                </button>
                            </div>
                            <div class="card-body">
                                @if ($room->maintenanceSchedules->count() > 0)
                                    <div class="list-group list-group-flush">
                                        @foreach ($room->maintenanceSchedules as $schedule)
                                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="d-flex align-items-center mb-1">
                                                        {!! $schedule->status_badge !!}
                                                        <span class="ms-2">{{ $schedule->date_range }}</span>
                                                    </div>
                                                    @if ($schedule->reason)
                                                        <small class="text-muted">{{ $schedule->reason }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted text-center mb-0">No upcoming maintenance scheduled</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <div class="col-lg-4">
                        {{-- Status --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Room Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span>Current Status:</span>
                                    {!! $room->status_badge !!}
                                </div>
                                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal"
                                    data-bs-target="#statusModal">
                                    <i class="bx bx-refresh me-1"></i> Change Status
                                </button>
                            </div>
                        </div>

                        {{-- Amenities --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Amenities</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">Select available equipment and features</p>
                                @foreach ($amenities as $amenity)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="amenities[]"
                                            value="{{ $amenity->id }}" id="amenity{{ $amenity->id }}"
                                            {{ in_array($amenity->id, old('amenities', $room->amenities->pluck('id')->toArray())) ? 'checked' : '' }}>
                                        <label class="form-check-label d-flex align-items-center"
                                            for="amenity{{ $amenity->id }}">
                                            {!! $amenity->icon_html !!}
                                            <span class="ms-2">{{ $amenity->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                                @if ($amenities->isEmpty())
                                    <p class="text-muted mb-0">No amenities available</p>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Save Changes
                                    </button>
                                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Danger Zone --}}
                        <div class="card border-danger">
                            <div class="card-header bg-danger bg-opacity-10">
                                <h5 class="card-title mb-0 text-danger">Danger Zone</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small mb-3">
                                    Deleting a room is permanent and cannot be undone.
                                    Rooms with bookings cannot be deleted.
                                </p>
                                <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal"
                                    data-bs-target="#deleteModal">
                                    <i class="bx bx-trash me-1"></i> Delete Room
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Status Modal --}}
    <div class="modal fade" id="statusModal" tabindex="-1">
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
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="statusSelect"
                                onchange="toggleMaintenanceFields()">
                                <option value="active" {{ $room->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $room->status === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                                <option value="under_maintenance"
                                    {{ $room->status === 'under_maintenance' ? 'selected' : '' }}>Under Maintenance
                                </option>
                            </select>
                        </div>

                        <div id="maintenanceFields" class="{{ $room->status !== 'under_maintenance' ? 'd-none' : '' }}">
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
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Maintenance Modal --}}
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.rooms.status', $room) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="under_maintenance">
                    <div class="modal-header">
                        <h5 class="modal-title">Schedule Maintenance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="maintenance_start" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="maintenance_end" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea class="form-control" name="maintenance_reason" rows="2"
                                placeholder="e.g., Annual air conditioning maintenance"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Schedule Maintenance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1">
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
                            Are you sure you want to delete <strong>{{ $room->name }}</strong>?
                        </p>
                        <p class="text-center text-muted small">
                            This action cannot be undone. All images will be permanently deleted.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Hidden form for image actions (To avoid nested forms) --}}
    <form id="imageActionForm" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="_method" id="imageActionMethod" value="POST">
    </form>
@endsection

@push('scripts')
    <script>
        function toggleMaintenanceFields() {
            const select = document.getElementById('statusSelect');
            const fields = document.getElementById('maintenanceFields');

            if (select.value === 'under_maintenance') {
                fields.classList.remove('d-none');
            } else {
                fields.classList.add('d-none');
            }
        }

        function submitImageAction(url, method) {
            const form = document.getElementById('imageActionForm');
            const methodInput = document.getElementById('imageActionMethod');

            form.action = url;
            methodInput.value = method;
            form.submit();
        }
    </script>
@endpush
