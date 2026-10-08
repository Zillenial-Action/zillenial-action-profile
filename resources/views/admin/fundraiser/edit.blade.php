@extends('components.layout.app')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="page-title">Edit Program Fundraiser</h2>
                        <p class="page-subtitle mb-0">{{ $program->event->name ?? 'Event terhapus' }}</p>
                    </div>
                    <a href="{{ route('fundraiser.show', $program) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-megaphone me-2"></i>Form Program Fundraiser
                    </div>
                    <div class="card-body">
                        <x-form-alerts />

                        <form action="{{ route('fundraiser.update', $program) }}" method="POST">
                            @csrf
                            @method('PUT')

                            @include('admin.fundraiser._form')

                            <div class="d-flex gap-2 pt-3 mt-2 border-top">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Update
                                </button>
                                <a href="{{ route('fundraiser.index') }}" class="btn btn-outline-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
