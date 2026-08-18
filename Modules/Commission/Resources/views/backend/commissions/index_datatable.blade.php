@extends('backend.layouts.app')

@section('title') Liste des Commissions @endsection

@section('content')
<div class="card">
    <div class="card-body">

        <x-backend.section-header>
            <div>
                <h4 class="mb-0">Gestion des Commissions</h4>
                <p class="text-muted mb-0 small">Définissez et gérez les commissions globales ou spécifiques par salon.</p>
            </div>
            <x-slot name="toolbar">
                @can('add_commission')
                <x-buttons.create route='{{ route("backend.commissions.create") }}' title="Créer une commission" />
                @endcan
            </x-slot>
        </x-backend.section-header>

        <div class="row mt-4">
            <div class="col">
                <table id="datatable" class="table table-striped border table-responsive w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Titre de la commission</th>
                            <th>Type</th>
                            <th>Valeur</th>
                            <th>Salons affectés</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('after-styles')
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
<script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: true,
            responsive: true,
            ajax: '{{ route("backend.commissions.index_data") }}',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'title', name: 'title' },
                { data: 'commission_type', name: 'commission_type' },
                { data: 'commission_value', name: 'commission_value' },
                { data: 'branches', name: 'branches', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false, class: 'text-end' }
            ]
        });
    });
</script>
@endpush
