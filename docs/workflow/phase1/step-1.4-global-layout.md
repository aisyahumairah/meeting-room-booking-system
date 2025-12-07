# Step 1.4: Global Layout & Navigation

**Priority:** HIGH | **Ref:** §4.4 | **Dependencies:** Step 1.3

---

## Objective
Create the main app layout with sidebar, navbar, and role-based menu visibility.

---

## Task 1.4.1: Create Main App Layout

**File:** `resources/views/layouts/app.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/layout.html`

Structure:
```html
<!DOCTYPE html>
<html>
<head>
    <!-- Meta, CSS (same as auth layout) -->
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            @include('layouts.partials.sidebar')
            
            <div class="layout-page">
                @include('layouts.partials.navbar')
                
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @yield('content')
                    </div>
                    
                    @include('layouts.partials.footer')
                </div>
            </div>
        </div>
    </div>
    
    <!-- Core JS -->
    @include('layouts.partials.scripts')
    @stack('scripts')
</body>
</html>
```

---

## Task 1.4.2: Create Sidebar Partial

**File:** `resources/views/layouts/partials/sidebar.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/layout.html` (sidebar section)

Menu items with role-based visibility:

| Menu Item | Icon | Route | Visible To |
|-----------|------|-------|------------|
| Dashboard | bx-home | dashboard.* | All |
| Meeting Rooms | bx-building | rooms.* | All |
| My Bookings | bx-calendar-check | bookings.my | All |
| Calendar | bx-calendar | bookings.calendar | All |
| --- Separator --- | | | Admin/Director |
| All Bookings | bx-list-ul | admin.bookings | Admin, Director |
| Approvals | bx-check-circle | admin.approvals | Admin, Director |
| Reports | bx-chart | admin.reports | Admin, Director, SysAdmin |
| --- Separator --- | | | Director/SysAdmin |
| User Management | bx-user | admin.users | Director, SysAdmin |
| Audit Logs | bx-history | admin.audit | Director, SysAdmin |
| System Settings | bx-cog | admin.settings | SysAdmin |

**Example Blade code:**
```php
<li class="menu-item {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
    <a href="{{ route('dashboard.user') }}" class="menu-link">
        <i class="menu-icon bx bx-home"></i>
        <div>Dashboard</div>
    </a>
</li>

@can('manage-bookings')
<li class="menu-header small text-uppercase">
    <span>Administration</span>
</li>
<li class="menu-item">
    <a href="{{ route('admin.approvals') }}" class="menu-link">
        <i class="menu-icon bx bx-check-circle"></i>
        <div>Approvals</div>
        @if($pendingCount ?? 0 > 0)
            <span class="badge bg-danger">{{ $pendingCount }}</span>
        @endif
    </a>
</li>
@endcan
```

---

## Task 1.4.3: Create Navbar Partial

**File:** `resources/views/layouts/partials/navbar.blade.php`

**Mockup Reference:** `mrbs-mock-up/pages/layout.html` (navbar section)

Elements:
- Sidebar toggle button (mobile)
- Search bar (optional for Phase 1)
- User dropdown:
  - User name and role badge
  - My Profile link
  - Change Password link
  - Logout button

```php
<li class="nav-item navbar-dropdown dropdown-user dropdown">
    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
            <span class="avatar-initial rounded-circle bg-primary">
                {{ substr(Auth::user()->name, 0, 1) }}
            </span>
        </div>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <div class="dropdown-item">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <span class="fw-semibold">{{ Auth::user()->name }}</span>
                        <small class="text-muted d-block">{{ Auth::user()->role_display }}</small>
                    </div>
                </div>
            </div>
        </li>
        <li><div class="dropdown-divider"></div></li>
        <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bx bx-user me-2"></i> My Profile</a></li>
        <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bx bx-lock me-2"></i> Change Password</a></li>
        <li><div class="dropdown-divider"></div></li>
        <li>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="dropdown-item"><i class="bx bx-power-off me-2"></i> Logout</button>
            </form>
        </li>
    </ul>
</li>
```

---

## Task 1.4.4: Create Footer Partial

**File:** `resources/views/layouts/partials/footer.blade.php`

```php
<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl d-flex flex-wrap justify-content-between py-2">
        <div class="mb-2 mb-md-0">
            © {{ date('Y') }} Oriental Interest Group - MRBS
        </div>
    </div>
</footer>
```

---

## Task 1.4.5: Create Scripts Partial

**File:** `resources/views/layouts/partials/scripts.blade.php`

```php
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<script src="{{ asset('assets/js/main.js') }}"></script>
```

---

## Task 1.4.6: Create Toast Component

**File:** `resources/views/components/toast.blade.php`

For displaying success/error messages:

```php
@if(session('success'))
<div class="bs-toast toast fade show bg-success position-fixed bottom-0 end-0 m-3" role="alert">
    <div class="toast-header">
        <i class="bx bx-check me-2"></i>
        <span class="me-auto fw-semibold">Success</span>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">{{ session('success') }}</div>
</div>
@endif

@if(session('error'))
<div class="bs-toast toast fade show bg-danger position-fixed bottom-0 end-0 m-3" role="alert">
    <div class="toast-header">
        <i class="bx bx-error me-2"></i>
        <span class="me-auto fw-semibold">Error</span>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body">{{ session('error') }}</div>
</div>
@endif
```

Include in app layout: `@include('components.toast')`

---

## Task 1.4.7: Share Pending Count with Views

**File:** `app/Providers/AppServiceProvider.php`

```php
use Illuminate\Support\Facades\View;

public function boot(): void
{
    // Share pending approvals count for sidebar badge
    View::composer('layouts.partials.sidebar', function ($view) {
        $pendingCount = 0;
        if (auth()->check() && auth()->user()->canManageBookings()) {
            // Will be implemented in Phase 3
            // $pendingCount = Booking::where('status', 'pending')->count();
        }
        $view->with('pendingCount', $pendingCount);
    });
}
```

---

## Acceptance Criteria
- [x] App layout extends mockup design
- [x] Sidebar shows role-appropriate menu items
- [x] Active menu item is highlighted
- [x] Navbar shows user info and dropdown
- [x] Logout form works via POST
- [x] Toast notifications display for session messages
- [x] Layout is responsive (sidebar collapses on mobile)

---

**Next:** [Step 1.5 - User Profile](./step-1.5-user-profile.md)
