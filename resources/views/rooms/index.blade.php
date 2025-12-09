@extends('layouts.app')

@section('title', 'Meeting Rooms')

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">Meeting Rooms</h4>
                    <p class="text-muted mb-0">Browse and book available meeting rooms</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary fs-6">{{ $totalRooms }} rooms available</span>
                </div>
            </div>

            {{-- Filter Bar (placeholder for Step 2.5) --}}
            <div class="card mb-4">
                <div class="card-body py-3">
                    <form action="{{ route('rooms.index') }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-search"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Search rooms..."
                                    value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="capacity">
                                <option value="">Any Capacity</option>
                                <option value="5" {{ request('capacity') == '5' ? 'selected' : '' }}>Up to 5</option>
                                <option value="10" {{ request('capacity') == '10' ? 'selected' : '' }}>Up to 10</option>
                                <option value="20" {{ request('capacity') == '20' ? 'selected' : '' }}>Up to 20</option>
                                <option value="50" {{ request('capacity') == '50' ? 'selected' : '' }}>Up to 50</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-filter-alt me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Room Grid --}}
            <div class="row">
                @forelse($rooms as $room)
                    <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
                        <div class="card h-100 room-card">
                            <div class="position-relative">
                                <img src="{{ $room->primary_image }}" class="card-img-top room-card-image"
                                    alt="{{ $room->name }}">
                                <div class="position-absolute top-0 end-0 m-2">
                                    {!! $room->status_badge !!}
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title mb-2">{{ $room->name }}</h5>
                                <p class="text-muted mb-2">
                                    <i class="bx bx-user"></i> {{ $room->capacity }} people
                                    <span class="mx-2">|</span>
                                    <i class="bx bx-map"></i> {{ $room->floor_location }}
                                </p>

                                {{-- Amenities --}}
                                @if ($room->amenities->count() > 0)
                                    <div class="room-amenities mb-3">
                                        @foreach ($room->amenities->take(4) as $amenity)
                                            <span class="badge bg-label-secondary me-1" title="{{ $amenity->name }}">
                                                {!! $amenity->icon_html !!}
                                            </span>
                                        @endforeach
                                        @if ($room->amenities->count() > 4)
                                            <span
                                                class="badge bg-label-secondary">+{{ $room->amenities->count() - 4 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer bg-transparent border-top-0 pt-0">
                                <a href="{{ route('rooms.show', $room) }}" class="btn btn-primary w-100">
                                    <i class="bx bx-show me-1"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bx bx-building bx-lg text-muted mb-3"></i>
                                <h5 class="text-muted">No rooms found</h5>
                                <p class="text-muted mb-0">Try adjusting your search or filters</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($rooms->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $rooms->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/rooms.css') }}">
@endpush
