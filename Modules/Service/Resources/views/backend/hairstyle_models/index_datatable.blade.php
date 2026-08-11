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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="hairstyleModelModalLabel">Créer un modèle de coiffure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="hairstyle-model-form" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="form-method" value="POST">
                    <input type="hidden" name="id" id="model-id">
                    <div id="removed-image-ids-container"></div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="service_id" class="form-label fw-semibold">Service <span class="text-danger">*</span></label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Sélectionner un service...</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 form-group mb-3">
                                <label for="status_select" class="form-label fw-semibold">Statut <span class="text-danger">*</span></label>
                                <select class="form-select" id="status_select" name="status" required>
                                    <option value="1" selected>Public</option>
                                    <option value="0">Privé</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name" class="form-label fw-semibold">Nom du modèle <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Ex: Afro Taper, Dégradé Américain, Twist Vanille, Locks Crochet...">
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label fw-semibold">Description / Détails du modèle</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description courte, conseils de style ou d'entretien..."></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="feature_image" class="form-label fw-semibold">
                                Choisir des images du modèle <span class="text-danger req-img-asterisk">*</span>
                                <small class="text-primary fw-normal ms-1">(Maintenez Ctrl pour sélectionner plusieurs images à la fois)</small>
                            </label>
                            <input type="file" class="form-control" id="feature_image" name="feature_image[]" accept="image/*" multiple>
                            
                            <div id="image-preview" class="mt-3 d-none">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span id="preview-count" class="form-text fw-bold text-primary m-0"></span>
                                    <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>Cliquez sur <strong>&times;</strong> sur une image pour la supprimer</small>
                                </div>
                                <div id="preview-images" class="d-flex flex-wrap gap-3 p-3 rounded border bg-light align-items-center"></div>
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

    <!-- Modal Galerie Futuriste (Next-Gen Glassmorphic) -->
    <div class="modal fade" id="futuristic-gallery-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 overflow-hidden shadow-lg" style="border-radius: 24px; background: rgba(13, 17, 28, 0.94); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.12); color: #fff;">
                <div class="modal-header border-0 px-4 pt-4 pb-2 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-45 rounded-circle d-flex align-items-center justify-content-center text-primary" style="background: rgba(99, 102, 241, 0.2); color: #818cf8; border: 1px solid rgba(129, 140, 248, 0.3);">
                            <i class="fa-solid fa-scissors fa-lg"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold text-white mb-0" id="fg-model-name">Galerie Modèle</h4>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge px-3 py-1 rounded-pill" id="fg-service-name" style="background: rgba(99, 102, 241, 0.25); color: #a5b4fc; border: 1px solid rgba(165, 180, 252, 0.2); font-size: 0.75rem;">Service</span>
                                <span class="badge px-3 py-1 rounded-pill" id="fg-photo-counter" style="font-size: 0.75rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);">1 / 1 Photos</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close" style="background-color: rgba(255,255,255,0.1);"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Description optional -->
                    <div id="fg-description-box" class="p-3 mb-3 rounded-3 d-none" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08);">
                        <small class="text-white-50 d-block mb-1 font-weight-bold">Details du modèle :</small>
                        <p class="text-light mb-0 small" id="fg-description"></p>
                    </div>

                    <!-- Main Viewer Stage -->
                    <div class="position-relative text-center rounded-4 overflow-hidden mb-3 d-flex align-items-center justify-content-center" style="background: radial-gradient(circle, rgba(30,41,59,0.7) 0%, rgba(15,23,42,0.95) 100%); min-height: 440px; max-height: 560px; border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: inset 0 0 40px rgba(0,0,0,0.8);">
                        <img id="fg-main-img" src="" class="img-fluid rounded-4 transition-all" style="max-height: 530px; object-fit: contain; filter: drop-shadow(0 15px 25px rgba(0,0,0,0.6));">

                        <!-- Left & Right Arrow controls -->
                        <button type="button" id="fg-btn-prev" class="btn position-absolute top-50 start-0 translate-middle-y ms-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); color: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.5); transition: all 0.2s;">
                            <i class="fa-solid fa-chevron-left fa-lg"></i>
                        </button>
                        <button type="button" id="fg-btn-next" class="btn position-absolute top-50 end-0 translate-middle-y me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); color: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.5); transition: all 0.2s;">
                            <i class="fa-solid fa-chevron-right fa-lg"></i>
                        </button>
                    </div>

                    <!-- Thumbnails Carousel Strip -->
                    <div id="fg-thumbnails-container" class="d-flex align-items-center justify-content-center gap-3 overflow-x-auto py-2"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex justify-content-between align-items-center">
                    <a id="fg-btn-download" href="#" target="_blank" download class="btn btn-outline-light btn-sm rounded-pill px-3" style="border-color: rgba(255,255,255,0.2);">
                        <i class="fa-solid fa-download me-1"></i> Télécharger l'image
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" id="fg-btn-edit" class="btn btn-primary btn-sm rounded-pill px-4" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Modifier ce modèle
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Suppression Sélective des Images -->
    <div class="modal fade" id="delete-images-modal" tabindex="-1" aria-labelledby="deleteImagesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-soft-danger border-bottom">
                    <h5 class="modal-title fw-bold text-danger" id="deleteImagesModalLabel">
                        <i class="fa-solid fa-trash-can me-2"></i>Supprimer des images
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-muted">
                        Ce modèle contient <strong id="dim-total-count" class="text-dark">0</strong> image(s). Cochez la ou les image(s) que vous souhaitez supprimer :
                    </p>
                    
                    <div class="d-flex align-items-center justify-content-between p-3 mb-3 bg-light rounded border">
                        <div class="form-check m-0">
                            <input type="checkbox" class="form-check-input cursor-pointer" id="dim-select-all">
                            <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" for="dim-select-all">
                                Tout cocher / Décocher tout (<span id="dim-selected-count">0</span> sélectionnée(s))
                            </label>
                        </div>
                        <small class="text-danger fw-semibold" id="dim-all-warning" style="display:none;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Toutes les images cochées : le modèle sera supprimé.
                        </small>
                    </div>

                    <div class="row g-3" id="dim-images-grid"></div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="dim-btn-submit">
                        <i class="fa-solid fa-trash me-1"></i> Supprimer les images sélectionnées
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
    <style>
        .hover-scale {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .hover-scale:hover {
            transform: scale(1.08);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4) !important;
        }
        .preview-thumb-wrapper {
            position: relative;
            display: inline-block;
        }
        .preview-thumb-wrapper .btn-delete-thumb {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background-color: #ef4444;
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: all 0.15s ease;
            z-index: 10;
        }
        .preview-thumb-wrapper .btn-delete-thumb:hover {
            background-color: #dc2626;
            transform: scale(1.2);
        }
        .fg-thumb {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            opacity: 0.5;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }
        .fg-thumb:hover, .fg-thumb.active {
            opacity: 1;
            border-color: #818cf8;
            box-shadow: 0 0 15px rgba(129, 140, 248, 0.7);
            transform: translateY(-3px);
        }
        .dim-img-card {
            transition: all 0.2s ease;
        }
        .dim-img-card:hover {
            border-color: #ef4444 !important;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2) !important;
        }
    </style>
@endpush

@push('after-scripts')
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', (event) => {
            // Global state for form image files & existing images
            let selectedNewFiles = [];
            let existingMediaItems = [];
            let removedMediaIds = [];

            // Futuristic Gallery Modal state
            let galleryData = null;
            let currentGalleryIndex = 0;

            // Selective Image Deletion Modal state
            let dimDeleteUrl = '';
            let dimDestroyUrl = '';
            let dimImagesList = [];

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
                    data: 'service',
                    name: 'service.name',
                    title: 'Service'
                },
                {
                    data: 'name',
                    name: 'name',
                    title: 'Nom du modèle'
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

            // Helper to sync selectedNewFiles back to <input type="file"> via DataTransfer
            function syncFileInput() {
                const dt = new DataTransfer();
                selectedNewFiles.forEach(file => dt.items.add(file));
                document.getElementById('feature_image').files = dt.files;
            }

            // Render form preview thumbnails
            function renderFormPreviews() {
                const $previewImages = $('#preview-images');
                $previewImages.empty();
                $('#removed-image-ids-container').empty();

                // Append hidden inputs for removed media IDs
                removedMediaIds.forEach(id => {
                    $('#removed-image-ids-container').append(`<input type="hidden" name="remove_image_ids[]" value="${id}">`);
                });

                let totalCount = 0;

                // Render existing media items (for Edit mode)
                existingMediaItems.forEach((item) => {
                    totalCount++;
                    const html = `
                        <div class="preview-thumb-wrapper me-1 mb-1" data-media-id="${item.id}">
                            <img src="${item.url}" class="rounded border shadow-sm" style="width: 70px; height: 70px; object-fit: cover;">
                            <span class="btn-delete-thumb remove-existing-media" data-media-id="${item.id}" title="Supprimer cette image">&times;</span>
                        </div>
                    `;
                    $previewImages.append(html);
                });

                // Render newly selected files
                selectedNewFiles.forEach((file, index) => {
                    totalCount++;
                    const objectUrl = URL.createObjectURL(file);
                    const isFirst = (totalCount === 1);
                    const badgeHtml = isFirst ? '<span class="badge bg-primary position-absolute bottom-0 start-0 m-1" style="font-size:0.6rem;">Principale</span>' : '';

                    const html = `
                        <div class="preview-thumb-wrapper me-1 mb-1" data-file-index="${index}">
                            <img src="${objectUrl}" class="rounded border shadow-sm" style="width: 70px; height: 70px; object-fit: cover;">
                            ${badgeHtml}
                            <span class="btn-delete-thumb remove-new-file" data-file-index="${index}" title="Retirer cette image">&times;</span>
                        </div>
                    `;
                    $previewImages.append(html);
                });

                if (totalCount > 0) {
                    $('#preview-count').text(totalCount > 1 ? `${totalCount} images au total` : '1 image sélectionnée');
                    $('#image-preview').removeClass('d-none');
                } else {
                    $('#image-preview').addClass('d-none');
                    $('#preview-count').text('');
                }
            }

            function resetHairstyleModelForm() {
                $('#hairstyleModelModalLabel').text('Créer un modèle de coiffure');
                $('#hairstyle-model-form')[0].reset();
                $('#form-method').val('POST');
                $('#model-id').val('');
                $('#service_id').val('');
                $('#status_select').val('1');
                $('#description').val('');
                selectedNewFiles = [];
                existingMediaItems = [];
                removedMediaIds = [];
                syncFileInput();
                renderFormPreviews();
            }

            // Reset modal on Open for creation
            $('#btn-create-model').on('click', function() {
                resetHairstyleModelForm();
                $('.req-img-asterisk').show();
            });

            // File selection event
            $('#feature_image').on('change', function() {
                const files = Array.from(this.files);
                files.forEach(file => {
                    selectedNewFiles.push(file);
                });
                syncFileInput();
                renderFormPreviews();
            });

            // Delete existing media item click
            $(document).on('click', '.remove-existing-media', function() {
                const mediaId = parseInt($(this).data('media-id'));
                removedMediaIds.push(mediaId);
                existingMediaItems = existingMediaItems.filter(item => item.id !== mediaId);
                renderFormPreviews();
            });

            // Remove newly selected file click
            $(document).on('click', '.remove-new-file', function() {
                const fileIdx = parseInt($(this).data('file-index'));
                selectedNewFiles.splice(fileIdx, 1);
                syncFileInput();
                renderFormPreviews();
            });

            // Edit Modal Populate
            $(document).on('click', '[data-crud-id]', function() {
                const id = $(this).data('crud-id');
                resetHairstyleModelForm();
                $('#hairstyleModelModalLabel').text('Modifier le modèle de coiffure');
                $('.req-img-asterisk').hide();
                
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

                            if (data.feature_image_items && data.feature_image_items.length > 0) {
                                existingMediaItems = [...data.feature_image_items];
                            } else if (data.feature_image_url) {
                                existingMediaItems = [{ id: 0, url: data.feature_image_url }];
                            }
                            renderFormPreviews();
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

                // Validate that at least one image is present
                if (!isEdit && selectedNewFiles.length === 0) {
                    Swal.fire({
                        title: 'Image manquante',
                        text: 'Veuillez obligatoirement sélectionner au moins une image pour ce modèle.',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'D\'accord'
                    });
                    return;
                }
                if (isEdit && selectedNewFiles.length === 0 && existingMediaItems.length === 0) {
                    Swal.fire({
                        title: 'Image manquante',
                        text: 'Le modèle doit conserver au moins une image.',
                        icon: 'warning',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'D\'accord'
                    });
                    return;
                }

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

            // -------------------------------------------------------------
            // Futuristic Gallery Modal Handler
            // -------------------------------------------------------------
            $(document).on('click', '.open-futuristic-gallery', function() {
                const infoStr = $(this).attr('data-model-info');
                if (!infoStr) return;
                
                try {
                    galleryData = JSON.parse(infoStr);
                } catch(e) {
                    return;
                }

                if (!galleryData || !galleryData.items || galleryData.items.length === 0) {
                    return;
                }

                currentGalleryIndex = 0;
                $('#fg-model-name').text(galleryData.name);
                $('#fg-service-name').text(galleryData.service || 'Coiffure');
                
                if (galleryData.description && galleryData.description.trim() !== '') {
                    $('#fg-description').text(galleryData.description);
                    $('#fg-description-box').removeClass('d-none');
                } else {
                    $('#fg-description-box').addClass('d-none');
                }

                $('#fg-btn-edit').attr('data-crud-id', galleryData.id);

                renderGalleryState();
                $('#futuristic-gallery-modal').modal('show');
            });

            function renderGalleryState() {
                if (!galleryData || !galleryData.items || galleryData.items.length === 0) return;

                const items = galleryData.items;
                const total = items.length;
                
                if (currentGalleryIndex < 0) currentGalleryIndex = total - 1;
                if (currentGalleryIndex >= total) currentGalleryIndex = 0;

                const currentItem = items[currentGalleryIndex];
                const imgUrl = currentItem.url || currentItem;

                $('#fg-main-img').attr('src', imgUrl);
                $('#fg-btn-download').attr('href', imgUrl);
                $('#fg-photo-counter').text(`${currentGalleryIndex + 1} / ${total} Photos`);

                // Hide/show arrows if single item
                if (total <= 1) {
                    $('#fg-btn-prev, #fg-btn-next').addClass('d-none');
                } else {
                    $('#fg-btn-prev, #fg-btn-next').removeClass('d-none');
                }

                // Render Carousel Thumbnails
                const $thumbsContainer = $('#fg-thumbnails-container');
                $thumbsContainer.empty();

                items.forEach((item, idx) => {
                    const url = item.url || item;
                    const activeClass = (idx === currentGalleryIndex) ? 'active' : '';
                    $thumbsContainer.append(`
                        <img src="${url}" class="fg-thumb ${activeClass}" data-idx="${idx}">
                    `);
                });
            }

            $('#fg-btn-prev').on('click', function() {
                currentGalleryIndex--;
                renderGalleryState();
            });

            $('#fg-btn-next').on('click', function() {
                currentGalleryIndex++;
                renderGalleryState();
            });

            $(document).on('click', '.fg-thumb', function() {
                currentGalleryIndex = parseInt($(this).data('idx'));
                renderGalleryState();
            });

            $('#fg-btn-edit').on('click', function() {
                $('#futuristic-gallery-modal').modal('hide');
                const id = $(this).attr('data-crud-id');
                if (id) {
                    setTimeout(() => {
                        $(`[data-crud-id="${id}"]`).first().trigger('click');
                    }, 300);
                }
            });

            // Keyboard navigation for gallery
            $(document).on('keydown', function(e) {
                if ($('#futuristic-gallery-modal').hasClass('show')) {
                    if (e.key === 'ArrowLeft') {
                        currentGalleryIndex--;
                        renderGalleryState();
                    } else if (e.key === 'ArrowRight') {
                        currentGalleryIndex++;
                        renderGalleryState();
                    }
                }
            });

            // -------------------------------------------------------------
            // Selective Image Deletion Modal Logic (Multi-Image Models)
            // -------------------------------------------------------------
            $(document).on('click', '.btn-open-delete-images-modal', function() {
                const modelName = $(this).attr('data-model-name') || 'Modèle';
                dimDeleteUrl = $(this).attr('data-delete-url');
                dimDestroyUrl = $(this).attr('data-destroy-url');
                const imagesStr = $(this).attr('data-images');

                try {
                    dimImagesList = JSON.parse(imagesStr);
                } catch(e) {
                    dimImagesList = [];
                }

                if (!dimImagesList || dimImagesList.length === 0) return;

                $('#deleteImagesModalLabel').html(`<i class="fa-solid fa-trash-can me-2"></i>Supprimer des images - ${modelName}`);
                $('#dim-total-count').text(dimImagesList.length);
                $('#dim-selected-count').text('0');
                $('#dim-select-all').prop('checked', false);
                $('#dim-all-warning').hide();

                const $grid = $('#dim-images-grid');
                $grid.empty();

                dimImagesList.forEach((item, idx) => {
                    const html = `
                        <div class="col-6 col-sm-4 col-md-3">
                            <div class="card h-100 border text-center p-2 position-relative shadow-sm dim-img-card cursor-pointer">
                                <div class="form-check position-absolute top-0 start-0 m-2" style="z-index: 10;">
                                    <input type="checkbox" class="form-check-input dim-img-checkbox cursor-pointer" value="${item.id}" id="dim-chk-${item.id}">
                                </div>
                                <label for="dim-chk-${item.id}" class="m-0 d-block cursor-pointer">
                                    <img src="${item.url}" class="img-fluid rounded border mb-2" style="height: 110px; width: 100%; object-fit: cover;">
                                    <span class="small fw-semibold text-muted d-block">Photo ${idx + 1}</span>
                                </label>
                            </div>
                        </div>
                    `;
                    $grid.append(html);
                });

                $('#delete-images-modal').modal('show');
            });

            // Select all / Deselect all
            $(document).on('change', '#dim-select-all', function() {
                const isChecked = $(this).is(':checked');
                $('.dim-img-checkbox').prop('checked', isChecked);
                updateDimState();
            });

            $(document).on('change', '.dim-img-checkbox', function() {
                updateDimState();
            });

            function updateDimState() {
                const total = $('.dim-img-checkbox').length;
                const checkedCount = $('.dim-img-checkbox:checked').length;
                $('#dim-selected-count').text(checkedCount);

                if (checkedCount === total && total > 0) {
                    $('#dim-select-all').prop('checked', true);
                    $('#dim-all-warning').show();
                } else {
                    $('#dim-select-all').prop('checked', false);
                    $('#dim-all-warning').hide();
                }
            }

            // Submit selective deletion
            $('#dim-btn-submit').on('click', function() {
                const checkedMediaIds = [];
                $('.dim-img-checkbox:checked').each(function() {
                    checkedMediaIds.push($(this).val());
                });

                if (checkedMediaIds.length === 0) {
                    if (window.toastr) {
                        toastr.warning('Veuillez cocher au moins une image à supprimer.');
                    } else {
                        alert('Veuillez cocher au moins une image à supprimer.');
                    }
                    return;
                }

                const isAllSelected = (checkedMediaIds.length === dimImagesList.length);
                const confirmMsg = isAllSelected 
                    ? 'Vous avez sélectionné TOUTES les images. Le modèle de coiffure sera également supprimé. Continuer ?'
                    : `Voulez-vous vraiment supprimer les ${checkedMediaIds.length} image(s) sélectionnée(s) ?`;

                const btnSubmit = $(this);

                Swal.fire({
                    title: 'Êtes-vous sûr(e) ?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        btnSubmit.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Suppression...');

                        $.ajax({
                            url: dimDeleteUrl,
                            type: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                media_ids: checkedMediaIds,
                                delete_model_if_empty: isAllSelected ? 1 : 0
                            },
                            success: function(res) {
                                btnSubmit.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i> Supprimer les images sélectionnées');
                                if (res.status) {
                                    if (window.toastr) {
                                        toastr.success(res.message);
                                    }
                                    $('#delete-images-modal').modal('hide');
                                    if (window.renderedDataTable) {
                                        window.renderedDataTable.ajax.reload(null, false);
                                    } else {
                                        location.reload();
                                    }
                                } else {
                                    if (window.toastr) {
                                        toastr.error(res.message || 'Erreur lors de la suppression');
                                    }
                                }
                            },
                            error: function(xhr) {
                                btnSubmit.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i> Supprimer les images sélectionnées');
                                if (window.toastr) {
                                    toastr.error('Erreur lors de la suppression des images.');
                                }
                            }
                        });
                    }
                });
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
