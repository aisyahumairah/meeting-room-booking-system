@extends('layouts.app')

@section('title', 'Add New Room')

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-icon btn-outline-secondary me-3">
                    <i class="bx bx-arrow-back"></i>
                </a>
                <div>
                    <h4 class="mb-1">Add New Room</h4>
                    <p class="text-muted mb-0">Create a new meeting room with amenities and photos</p>
                </div>
            </div>

            <form action="{{ route('admin.rooms.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

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
                                            id="name" name="name" value="{{ old('name') }}"
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
                                                name="capacity" value="{{ old('capacity') }}" min="1" max="500"
                                                required>
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
                                            id="floor_location" name="floor_location" value="{{ old('floor_location') }}"
                                            placeholder="e.g., Level 5" required maxlength="100">
                                        @error('floor_location')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="description">Description</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                            rows="3" placeholder="Optional description of the room..." maxlength="500">{{ old('description') }}</textarea>
                                        <small class="text-muted">Max 500 characters</small>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Room Photos --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Room Photos</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Upload Photos</label>
                                    <div class="dropzone-container border rounded p-4 text-center" id="dropzone">
                                        <i class="bx bx-cloud-upload bx-lg text-primary mb-2"></i>
                                        <p class="mb-2">Drag and drop images here, or click to select</p>
                                        <p class="text-muted small mb-3">JPEG or PNG, max 5MB each, up to 5 images</p>
                                        <input type="file"
                                            class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
                                            id="images" name="images[]" multiple accept="image/jpeg,image/png">
                                        @error('images')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @error('images.*')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div id="imagePreview" class="row g-3"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <div class="col-lg-4">
                        {{-- Status --}}
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Status <span class="text-danger">*</span></h5>
                            </div>
                            <div class="card-body">
                                <select class="form-select @error('status') is-invalid @enderror" name="status"
                                    id="status" required>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                        Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                        Inactive</option>
                                    <option value="under_maintenance"
                                        {{ old('status') == 'under_maintenance' ? 'selected' : '' }}>Under Maintenance
                                    </option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Set the initial status of the room</div>
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
                                            {{ in_array($amenity->id, old('amenities', [])) ? 'checked' : '' }}>
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
                        <div class="card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-save me-1"></i> Create Room
                                    </button>
                                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            const files = Array.from(e.target.files).slice(0, 5); // Max 5 images

            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-4';
                    col.innerHTML = `
                <div class="position-relative">
                    <img src="${e.target.result}" class="img-fluid rounded" alt="Preview ${index + 1}"
                        style="width: 100%; height: 100px; object-fit: cover;">
                    ${index === 0 ? '<span class="badge bg-primary position-absolute top-0 start-0 m-1">Primary</span>' : ''}
                </div>
            `;
                    preview.appendChild(col);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endpush

@push('styles')
    <style>
        .dropzone-container {
            border: 2px dashed #d9dee3;
            transition: border-color 0.3s;
            cursor: pointer;
        }

        .dropzone-container:hover {
            border-color: #696cff;
        }
    </style>
@endpush
