@extends('layout.admin.app')

@section('title', 'Edit organization')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Onboarding / {{ $organization->name }}</div>
            <h2 class="page-title">Edit organization</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('admin.organizations.update', $organization) }}" method="POST">
                @csrf @method('PUT')
                <div class="card">
                    <div class="card-body">
                        @include('admin.organizations._form')
                    </div>
                    <div class="card-footer d-flex">
                        <a href="{{ route('admin.organizations.show', $organization) }}" class="btn btn-link">Cancel</a>
                        <button type="submit" class="btn btn-primary ms-auto">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
