@extends('layout.citizen.app')

@section('title', 'Home')

@section('content')
    <div class="page-header mb-4">
        <h2 class="page-title">Welcome, {{ auth()->user()->first_name }}</h2>
        <div class="text-secondary">What would you like to do?</div>
    </div>

    <div class="row row-cards">
        <div class="col-md-4">
            <a href="{{ route('request.create') }}" class="card card-link card-link-pop">
                <div class="card-body text-center">
                    <i class="ti ti-ambulance text-red" style="font-size: 2.5rem;"></i>
                    <h3 class="mt-3 mb-1">Request an ambulance</h3>
                    <div class="text-secondary">One-tap or detailed emergency request.</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('citizen.history') }}" class="card card-link card-link-pop">
                <div class="card-body text-center">
                    <i class="ti ti-map-pin text-blue" style="font-size: 2.5rem;"></i>
                    <h3 class="mt-3 mb-1">Track a request</h3>
                    <div class="text-secondary">Follow a request from your history.</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('citizen.profile') }}" class="card card-link card-link-pop">
                <div class="card-body text-center">
                    <i class="ti ti-user-cog text-green" style="font-size: 2.5rem;"></i>
                    <h3 class="mt-3 mb-1">My account</h3>
                    <div class="text-secondary">Profile, medical info, and history.</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Track by code — public tracking page accepts any reference code. --}}
    <div class="card mt-4">
        <div class="card-body">
            <form method="GET" action="{{ url('/request') }}" class="row g-2 align-items-end"
                  onsubmit="event.preventDefault(); const c=this.code.value.trim(); if(c) window.location='{{ url('/request') }}/'+encodeURIComponent(c);">
                <div class="col">
                    <label class="form-label">Track by reference code</label>
                    <input type="text" name="code" class="form-control" placeholder="REQ-XXXXXXXX">
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Track</button>
                </div>
            </form>
        </div>
    </div>

    <p class="text-secondary small mt-3 mb-0">Non-emergency &amp; scheduled requests — planned.</p>
@endsection
