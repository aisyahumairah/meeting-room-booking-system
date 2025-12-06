@extends('layouts.auth')

@section('title', 'Dashboard')

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <div class="card px-sm-6 px-0">
                    <div class="card-body text-center">
                        <h4 class="mb-4">Welcome, {{ auth()->user()->name }}! 👋</h4>
                        <p class="mb-4">Role: <strong>{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</strong>
                        </p>

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <p class="text-muted mb-4">User Dashboard - Coming in Step 1.6</p>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
