<div class="col-lg-4 col-md-6 mb-4">
    <div class="card h-100 room-card shadow-sm">
        <div class="position-relative">
            <img src="{{ $room->primary_image }}" class="card-img-top room-card-image" alt="{{ $room->name }}">
            <div class="position-absolute top-0 end-0 m-2">
                {!! $room->status_badge !!}
            </div>
        </div>
        <div class="card-body">
            <h5 class="card-title mb-2">{{ $room->name }}</h5>
            <p class="text-muted small mb-2">
                <i class="bx bx-user me-1"></i> {{ $room->capacity }} people
                <span class="mx-1">•</span>
                <i class="bx bx-map me-1"></i> {{ $room->floor_location }}
            </p>
            <div class="d-flex flex-wrap gap-1 mb-2">
                @foreach ($room->amenities->take(4) as $amenity)
                    <span class="badge bg-label-secondary" title="{{ $amenity->name }}">
                        {!! $amenity->icon_html !!}
                    </span>
                @endforeach
                @if ($room->amenities->count() > 4)
                    <span class="badge bg-label-secondary">+{{ $room->amenities->count() - 4 }}</span>
                @endif
            </div>
        </div>
        <div class="card-footer bg-transparent border-top-0 pt-0">
            <a href="{{ route('rooms.show', $room) }}" class="btn btn-primary w-100">
                <i class="bx bx-info-circle me-1"></i> View Details
            </a>
        </div>
    </div>
</div>
