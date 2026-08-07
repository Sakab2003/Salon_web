@extends('backend.layouts.app')

@section('title')
    {{ $module_action }} {{ $module_title }}
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <x-backend.section-header>
                <div class="d-flex flex-wrap gap-3">
                    @if(auth()->user()->can('edit_hairstyle_model') || auth()->user()->can('delete_hairstyle_model'))
                    <x-backend.quick-action url="{{ route('backend.hairstyle-models.bulk_action') }}">
                        <div>
                            <select name="action_type" class="form-control select2 col-12" id="quick-action-type" style="width:100%">
                                <option value="">{{ __('messages.no_action') }}</option>
                                @can('edit_hairstyle_model')
                                <option value="change-status">{{ __('messages.status') }}</option>
                                @endcan
                                @can('delete_hairstyle_model')
                                <option value="delete">{{ __('messages.delete') }}</option>
                                @endcan
                            </select>
                        </div>
                        <div class="select-status d-none quick-action-field" id="change-status-action">
                            <select name="status" class="form-control select2" id="status" style="width:100%">
                                <option value="1" selected>Public</option>
                                <option value="0">Privé</option>
                            </select>
                        </div>
                    </x-backend.quick-action>
                    @endif
                </div>

                <x-slot name="toolbar">
                    <div class="datatable-filter">
                        <select name="column_service" id="column_service" class="select2 form-control" data-filter="select" style="width: 100%">
                            <option value="">Tous les services</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" {{ isset($filter['service_id']) && $filter['service_id'] == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="datatable-filter">
                        <select name="column_status" id="column_status" class="select2 form-control" data-filter="select" style="width: 100%">
                            <option value="">Tous les statuts</option>
                            <option value="1" {{ isset($filter['status']) && $filter['status'] == '1' ? 'selected' : '' }}>Public</option>
                            <option value="0" {{ isset($filter['status']) && $filter['status'] == '0' ? 'selected' : '' }}>Privé</option>
                        </select>
                    </div>

                    <div class="input-group flex-nowrap">
                        <span class="input-group-text" id="addon-wrapping"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}..." aria-label="Search" aria-describedby="addon-wrapping">
                    </div>

                    @hasPermission('add_hairstyle_model')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hairstyle-model-modal" id="btn-create-model">
                            <i class="fa-solid fa-plus me-1"></i> Créer un modèle
                        </button>
                    @endhasPermission
                </x-slot>
            </x-backend.section-header>

            <table id="datatable" class="table table-striped border table-responsive">
            </table>
        </div>
    </div>

    <!-- Modal Créer / Modifier Modèle de Coiffure -->
    <div class="modal fade" id="hairstyle-model-modal" tabindex="-1" aria-labelledby="hairstyleModelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hairstyleModelModalLabel">Créer un modèle de coiffure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="hairstyle-model-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="form-method" value="POST">
                    <input type="hidden" name="id" id="model-id">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Nom du modèle <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Ex: Dégradé Afro, Tresses Africaines...">
                        </div>

                        <div class="form-group mb-3">
                            <label for="service_id" class="form-label">Service (Coiffure) <span class="text-danger">*</span></label>
                            <select class="form-select" id="service_id" name="service_id" required>
                                <option value="">Sélectionner un service de coiffure</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="status_select" class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select" id="status_select" name="status" required>
                                <option value="1" selected>Public</option>
                                <option value="0">Privé</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description / Détails du modèle</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description courte, conseils de style ou d'entretien..."></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="feature_image" class="form-label">Image du modèle</label>
                            <input type="file" class="form-control" id="feature_image" name="feature_image" accept="image/*">
                            <div id="image-preview" class="mt-2 d-none">
                                <img src="" id="preview-img" class="avatar avatar-80 rounded border" alt="Preview">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-model">Enregistrer</button>
                    </div>
                </form>
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
        document.addEventListener('DOMContentLoaded', (event) => {
            const columns = [
                {
                    name: 'check',
                    data: 'check',
                    title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                    width: '0%',
                    exportable: false,
                    orderable: false,
                    searchable: false,
                },
                {
                    data: 'image',
                    name: 'image',
                    title: 'Image',
                    orderable: false,
                    width: '5%'
                },
                {
                    data: 'name',
                    name: 'name',
                    title: 'Nom du modèle'
                },
                {
                    data: 'service',
                    name: 'service.name',
                    title: 'Service'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    title: 'Créé le'
                },
                {
                    data: 'updated_at',
                    name: 'updated_at',
                    title: 'Mise à jour le'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: true,
                    searchable: true,
                    title: 'Statut',
                    width: '10%'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    title: 'Action',
                    width: '10%'
                }
            ];

            initDatatable({
                url: '{{ route("backend.hairstyle-models.index_data") }}',
                finalColumns: columns,
                advanceFilter: () => {
                    return {
                        service_id: $('#column_service').val(),
                        column_status: $('#column_status').val(),
                    }
                }
            });

            $('#column_service, #column_status').on('change', function() {
                window.renderedDataTable.ajax.reload(null, false);
            });

            // Reset modal on Open for creation
            $('#btn-create-model').on('click', function() {
                $('#hairstyleModelModalLabel').text('Créer un modèle de coiffure');
                $('#hairstyle-model-form')[0].reset();
                $('#form-method').val('POST');
                $('#model-id').val('');
                $('#service_id').val('');
                $('#status_select').val('1');
                $('#description').val('');
                $('#image-preview').addClass('d-none');
            });

            // Edit Modal Populate
            $(document).on('click', '[data-crud-id]', function() {
                const id = $(this).data('crud-id');
                $('#hairstyleModelModalLabel').text('Modifier le modèle de coiffure');
                
                $.ajax({
                    url: `{{ url('app/hairstyle-models') }}/${id}/edit`,
                    type: 'GET',
                    success: function(res) {
                        if (res.status && res.data) {
                            const data = res.data;
                            $('#model-id').val(data.id);
                            $('#name').val(data.name);
                            $('#service_id').val(data.service_id);
                            $('#status_select').val(data.status);
                            $('#description').val(data.description || '');
                            $('#form-method').val('PUT');

                            if (data.feature_image_url) {
                                $('#preview-img').attr('src', data.feature_image_url);
                                $('#image-preview').removeClass('d-none');
                            } else {
                                $('#image-preview').addClass('d-none');
                            }
                        }
                    }
                });
            });

            // Submit Form via AJAX
            $('#hairstyle-model-form').on('submit', function(e) {
                e.preventDefault();
                const id = $('#model-id').val();
                const isEdit = $('#form-method').val() === 'PUT';
                const url = isEdit ? `{{ url('app/hairstyle-models') }}/${id}` : `{{ route('backend.hairstyle-models.store') }}`;

                const formData = new FormData(this);
                if (isEdit) {
                    formData.append('_method', 'PUT');
                }

                const btnSave = $('#btn-save-model');
                btnSave.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Enregistrement...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        btnSave.prop('disabled', false).html('Enregistrer');
                        if (res.status) {
                            if (window.toastr) {
                                toastr.success(res.message);
                            }
                            $('#hairstyle-model-modal').modal('hide');
                            if (window.renderedDataTable) {
                                window.renderedDataTable.ajax.reload(null, false);
                            } else {
                                location.reload();
                            }
                        } else {
                            if (window.toastr) {
                                toastr.error(res.message || 'Une erreur s est produite');
                            }
                        }
                    },
                    error: function(xhr) {
                        btnSave.prop('disabled', false).html('Enregistrer');
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                if (window.toastr) {
                                    toastr.error(errors[key][0]);
                                }
                            });
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            if (window.toastr) {
                                toastr.error(xhr.responseJSON.message);
                            }
                        } else {
                            if (window.toastr) {
                                toastr.error('Erreur lors de l enregistrement');
                            }
                        }
                    }
                });
            });

            // Image Preview handler
            $('#feature_image').on('change', function() {
                const [file] = this.files;
                if (file) {
                    $('#preview-img').attr('src', URL.createObjectURL(file));
                    $('#image-preview').removeClass('d-none');
                }
            });
        });

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');
                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        }

        $('#quick-action-type').change(function() {
            resetQuickAction();
        });
    </script>
@endpush
