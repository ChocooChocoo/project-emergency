@extends('layout.admin.app')

@section('title', 'New organization')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Onboarding</div>
            <h2 class="page-title">New organization</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('admin.organizations.store') }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-body">
                        @include('admin.organizations._form', ['organization' => null])
                    </div>
                    <div class="card-footer d-flex">
                        <a href="{{ route('admin.organizations.index') }}" class="btn btn-link">Cancel</a>
                        <button type="submit" class="btn btn-primary ms-auto">Create organization</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
