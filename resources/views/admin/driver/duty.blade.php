@extends('layout.admin.app')

@section('title', 'Driver Duty')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">S8 — Driver</div>
            <h2 class="page-title">Duty &amp; Assignments</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">

                {{-- Duty toggle --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Duty status</h3></div>
                        <div class="card-body">
                            @php($current = $duty?->status ?? 'off_duty')
                            <p class="mb-3">
                                Currently:
                                <span class="badge bg-{{ $current === 'on_duty' ? 'green' : ($current === 'break' ? 'yellow' : 'secondary') }}-lt">
                                    {{ ucwords(str_replace('_', ' ', $current)) }}
                                </span>
                            </p>
                            <form action="{{ route('admin.driver.duty.update') }}" method="POST">
                                @csrf @method('PATCH')
                                <div class="mb-3">
                                    <label class="form-label">Set status</label>
                                    <select name="status" class="form-select">
                                        @foreach (['on_duty' => 'On duty', 'break' => 'On break', 'off_duty' => 'Off duty'] as $val => $label)
                                            <option value="{{ $val }}" @selected($current === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Update duty</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Active assignments --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">My active assignments</h3></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr><th>Incident</th><th>Unit</th><th>Status</th><th class="text-center">Actions</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($assignments as $a)
                                            <tr>
                                                <td><a href="{{ route('admin.driver.assignment', $a) }}" class="fw-bold">{{ $a->incident?->request_code }}</a></td>
                                                <td>{{ $a->ambulance?->plate_no }}</td>
                                                <td><span class="badge bg-blue-lt">{{ ucwords(str_replace('_', ' ', $a->status)) }}</span></td>
                                                <td class="text-center">
                                                    <a href="{{ route('admin.driver.assignment', $a) }}" class="btn btn-sm btn-primary">Open</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-secondary py-4">No active assignments.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
