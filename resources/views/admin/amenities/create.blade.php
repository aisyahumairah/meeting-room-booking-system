@extends('layouts.app')

@section('title', 'Create New Amenity')

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Create New Amenity</h4>
                    <p class="text-muted mb-0">Add a new amenity for meeting rooms</p>
                </div>
                <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to List
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.amenities.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Amenity Name *</label>
                                    <input type="text" id="name" name="name"
                                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                        placeholder="e.g., Projector, Whiteboard" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">Icon *</label>
                                    <select id="icon" name="icon"
                                        class="form-select @error('icon') is-invalid @enderror" required>
                                        <option value="">Select an icon...</option>
                                        @foreach ($availableIcons as $iconClass => $iconName)
                                            <option value="{{ $iconClass }}" @selected(old('icon') == $iconClass)>
                                                {{ $iconName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div id="icon-preview" class="mt-2 fs-1 text-primary"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea id="description" name="description" rows="3"
                                class="form-control @error('description') is-invalid @enderror" placeholder="Brief description of the amenity...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Maximum 500 characters</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Create Amenity
                            </button>
                            <a href="{{ route('admin.amenities.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Live icon preview
        document.getElementById('icon').addEventListener('change', function() {
            const preview = document.getElementById('icon-preview');
            if (this.value) {
                preview.innerHTML = `<i class='bx ${this.value}'></i>`;
            } else {
                preview.innerHTML = '';
            }
        });

        // Show preview if old value exists
        if (document.getElementById('icon').value) {
            document.getElementById('icon').dispatchEvent(new Event('change'));
        }
    </script>
@endpush
