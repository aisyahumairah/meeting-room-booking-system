@extends('layouts.guest')

@section('title', 'Server Error')

@section('content')
    <div class="container-xxl container-p-y">
        <div class="misc-wrapper text-center">
            <h2 class="mb-2 mx-2">Something Went Wrong!</h2>
            <p class="mb-4 mx-2">
                We're sorry, but something went wrong on our end.
                <br>
                Our team has been notified and is working to fix the issue.
            </p>

            <div class="alert alert-light d-inline-block mb-4">
                <strong>Reference ID:</strong>
                <code>{{ session('error_id', Str::uuid()->toString()) }}</code>
                <br>
                <small class="text-muted">Please quote this ID when contacting support.</small>
            </div>

            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="bx bx-home me-1"></i> Return Home
                </a>
                <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()">
                    <i class="bx bx-refresh me-1"></i> Try Again
                </button>
            </div>

            <div class="mt-3">
                <img src="{{ asset('assets/img/illustrations/page-misc-error-light.png') }}" alt="Server Error"
                    width="400" class="img-fluid">
            </div>

            <div class="mt-4 text-muted">
                <small>Error Code: 500 | {{ now()->format('Y-m-d H:i:s') }}</small>
            </div>
        </div>
    </div>
@endsection
