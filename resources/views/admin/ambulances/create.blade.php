@extends('layout.admin.app')

@section('title', 'Register ambulance')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Fleet</div>
            <h2 class="page-title">Register ambulance</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('admin.ambulances.store') }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-body">
                        @include('admin.ambulances._form', ['ambulance' => null])
                    </div>
                    <div class="card-footer d-flex">
                        <a href="{{ route('admin.ambulances.index') }}" class="btn btn-link">Cancel</a>
                        <button type="submit" class="btn btn-primary ms-auto">Register ambulance</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
