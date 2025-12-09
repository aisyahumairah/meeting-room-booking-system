@extends('layouts.app')

@section('title', 'Meeting Rooms')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="mb-4">Meeting Rooms</h4>

        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 col-md-4">
                @include('rooms.partials.filters')
            </div>

            <!-- Room Grid -->
            <div class="col-lg-9 col-md-8">
                <!-- Applied Filters & Count -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <span class="text-muted">
                        <strong>{{ $rooms->total() }}</strong> room(s) found
                    </span>
                    @if (request()->hasAny(['search', 'capacity', 'amenities', 'date']))
                        <div class="applied-filters d-flex flex-wrap gap-1">
                            @if (request('search'))
                                <span class="badge bg-label-primary">
                                    <i class="bx bx-search me-1"></i>{{ request('search') }}
                                    <a href="{{ request()->fullUrlWithoutQuery('search') }}"
                                        class="ms-1 text-primary">&times;</a>
                                </span>
                            @endif
                            @if (request('capacity'))
                                <span class="badge bg-label-primary">
                                    <i class="bx bx-user me-1"></i>{{ request('capacity') }}+ people
                                    <a href="{{ request()->fullUrlWithoutQuery('capacity') }}"
                                        class="ms-1 text-primary">&times;</a>
                                </span>
                            @endif
                            @if (request('amenities'))
                                @php
                                    $selectedAmenities = $amenities->whereIn('id', (array) request('amenities'));
                                @endphp
                                @foreach ($selectedAmenities as $amenity)
                                    <span class="badge bg-label-primary">
                                        {!! $amenity->icon_html !!} {{ $amenity->name }}
                                    </span>
                                @endforeach
                            @endif
                            @if (request('date'))
                                <span class="badge bg-label-info">
                                    <i class="bx bx-calendar me-1"></i>{{ request('date') }}
                                    @if (request('start_time') && request('end_time'))
                                        {{ \Carbon\Carbon::parse(request('start_time'))->format('H:i') }}-{{ \Carbon\Carbon::parse(request('end_time'))->format('H:i') }}
                                    @endif
                                    <a href="{{ request()->fullUrlWithoutQuery(['date', 'start_time', 'end_time']) }}"
                                        class="ms-1 text-info">&times;</a>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Room Cards Grid -->
                @if ($rooms->isEmpty())
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bx bx-search-alt bx-lg text-muted mb-3 d-block" style="font-size: 4rem;"></i>
                            <h5>No rooms found</h5>
                            <p class="text-muted mb-4">Try adjusting your filters or search criteria.</p>
                            <a href="{{ route('rooms.index') }}" class="btn btn-primary">
                                <i class="bx bx-x me-1"></i>Clear Filters
                            </a>
                        </div>
                    </div>
                @else
                    <div class="row" id="roomGrid">
                        @foreach ($rooms as $room)
                            @include('rooms.partials.room-card', ['room' => $room])
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if ($rooms->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $rooms->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/rooms.css') }}">
    <style>
        .applied-filters .badge a {
            text-decoration: none;
            font-weight: bold;
        }

        .applied-filters .badge a:hover {
            opacity: 0.7;
        }

        .room-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .room-card-image {
            height: 180px;
            object-fit: cover;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/room-filters.js') }}"></script>
@endpush
