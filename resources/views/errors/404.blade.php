@extends('layouts.guest')

@section('title', 'Page Not Found')

@section('content')
    <div class="container-xxl container-p-y">
        <div class="misc-wrapper text-center">
            <h2 class="mb-2 mx-2">Page Not Found</h2>
            <p class="mb-4 mx-2">
                The page you're looking for doesn't exist or has been moved.
            </p>

            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                    <i class="bx bx-arrow-back me-1"></i> Go Back
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">
                        <i class="bx bx-home me-1"></i> Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="bx bx-home me-1"></i> Home
                    </a>
                @endauth
            </div>

            <div class="mt-3">
                <img src="{{ asset('assets/img/illustrations/page-misc-error-light.png') }}" alt="Page Not Found"
                    width="500" class="img-fluid">
            </div>

            <div class="mt-4 text-muted">
                <small>Error Code: 404 | {{ now()->format('Y-m-d H:i:s') }}</small>
            </div>
        </div>
    </div>
@endsection
