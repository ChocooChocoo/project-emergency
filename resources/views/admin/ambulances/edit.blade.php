@extends('layout.admin.app')

@section('title', 'Edit ambulance')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Fleet / {{ $ambulance->plate_no }}</div>
            <h2 class="page-title">Edit ambulance</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('admin.ambulances.update', $ambulance) }}" method="POST">
                @csrf @method('PUT')
                <div class="card">
                    <div class="card-body">
                        @include('admin.ambulances._form')
                    </div>
                    <div class="card-footer d-flex">
                        <a href="{{ route('admin.ambulances.show', $ambulance) }}" class="btn btn-link">Cancel</a>
                        <button type="submit" class="btn btn-primary ms-auto">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
