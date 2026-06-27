@extends('layout.admin.app')

@section('title', 'Dashboard')

@section('content')

    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Super Admin</div>
                    <h2 class="page-title">Dashboard</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                @php($cards = [
                    ['Total users', $stats['users'], 'ti-users', 'bg-blue'],
                    ['Active', $stats['active'], 'ti-user-check', 'bg-green'],
                    ['Awaiting approval', $stats['pending'], 'ti-user-question', 'bg-yellow'],
                    ['Archived', $stats['archived'], 'ti-archive', 'bg-secondary'],
                ])
                @foreach ($cards as [$label, $value, $icon, $bg])
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="avatar {{ $bg }} text-white me-3"><i class="ti {{ $icon }}"></i></span>
                                    <div>
                                        <div class="h1 mb-0">{{ $value }}</div>
                                        <div class="text-secondary">{{ $label }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection
