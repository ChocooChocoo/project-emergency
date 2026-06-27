@extends('layout.admin.app')

@section('title', 'City Settings')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Governance</div>
            <h2 class="page-title">City Settings</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form id="config-form" action="{{ route('admin.config.update') }}" method="POST">
                @csrf @method('PUT')
                <div class="card">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required" for="dss_timeout_seconds">Dispatch response timeout (seconds)</label>
                                <input type="number" class="form-control" id="dss_timeout_seconds" name="dss_timeout_seconds"
                                       min="5" max="600" required value="{{ old('dss_timeout_seconds', $dssTimeout) }}">
                                <small class="form-hint">How long an organization has to accept a dispatched incident before it can be reassigned. Applies city-wide.</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex">
                        <button type="button" class="btn btn-primary ms-auto"
                                onclick="confirmAction(() => document.getElementById('config-form').submit(), { type:'primary', title:'Save city settings?', message:'These rules apply city-wide.', confirm:'Save' })">
                            Save changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
