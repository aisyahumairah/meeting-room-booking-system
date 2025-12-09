<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary">
        <h5 class="mb-0 text-white"><i class="bx bx-filter-alt me-2"></i>Filter Rooms</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('rooms.index') }}" method="GET" id="filterForm">

            <!-- Search -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                        placeholder="Room name...">
                </div>
            </div>

            <!-- Capacity -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Minimum Capacity</label>
                <select name="capacity" class="form-select">
                    <option value="">Any</option>
                    @foreach ([2, 4, 6, 8, 10, 12, 15, 20, 25] as $cap)
                        <option value="{{ $cap }}" {{ request('capacity') == $cap ? 'selected' : '' }}>
                            {{ $cap }}+ people
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date & Time -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Check Availability</label>
                <input type="date" name="date" class="form-control mb-2" value="{{ request('date') }}"
                    min="{{ date('Y-m-d') }}">
                <div class="row g-2">
                    <div class="col-6">
                        <select name="start_time" class="form-select" id="startTimeSelect">
                            <option value="">Start</option>
                            @for ($h = 8; $h < 18; $h++)
                                @foreach (['00', '30'] as $m)
                                    @php $t = sprintf('%02d:%s:00', $h, $m); @endphp
                                    <option value="{{ $t }}"
                                        {{ request('start_time') == $t ? 'selected' : '' }}>
                                        {{ sprintf('%02d:%s', $h, $m) }}
                                    </option>
                                @endforeach
                            @endfor
                        </select>
                    </div>
                    <div class="col-6">
                        <select name="end_time" class="form-select" id="endTimeSelect">
                            <option value="">End</option>
                            @for ($h = 8; $h <= 18; $h++)
                                @foreach (['00', '30'] as $m)
                                    @if ($h == 18 && $m == '30')
                                        @continue
                                    @endif
                                    @php $t = sprintf('%02d:%s:00', $h, $m); @endphp
                                    <option value="{{ $t }}"
                                        {{ request('end_time') == $t ? 'selected' : '' }}>
                                        {{ sprintf('%02d:%s', $h, $m) }}
                                    </option>
                                @endforeach
                            @endfor
                        </select>
                    </div>
                </div>
                <small class="text-muted">Leave empty to show all rooms</small>
            </div>

            <!-- Amenities -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Amenities</label>
                <div class="amenities-list" style="max-height: 200px; overflow-y: auto;">
                    @foreach ($amenities as $amenity)
                        <div class="form-check">
                            <input type="checkbox" name="amenities[]" value="{{ $amenity->id }}"
                                class="form-check-input" id="amenity{{ $amenity->id }}"
                                {{ in_array($amenity->id, (array) request('amenities', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="amenity{{ $amenity->id }}">
                                {!! $amenity->icon_html !!} {{ $amenity->name }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <small class="text-muted">Room must have ALL selected</small>
            </div>

            <!-- Buttons -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-filter me-1"></i>Apply Filters
                </button>
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-x me-1"></i>Clear All
                </a>
            </div>
        </form>
    </div>
</div>
