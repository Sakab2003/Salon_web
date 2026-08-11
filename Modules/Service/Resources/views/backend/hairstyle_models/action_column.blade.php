<div class="d-flex justify-content-end align-items-center gap-2">
    @can('edit_hairstyle_model')
        <button type="button" 
            class="btn btn-soft-primary btn-sm rounded" 
            data-crud-id="{{ $data->id }}" 
            data-bs-toggle="modal" 
            data-bs-target="#hairstyle-model-modal"
            title="Modifier">
            <i class="fa-solid fa-pen-to-square"></i>
        </button>
    @endcan

    @can('delete_hairstyle_model')
        @if(count($data->feature_image_items) > 1)
            <button type="button" 
                class="btn btn-soft-danger btn-sm rounded btn-open-delete-images-modal" 
                data-model-id="{{ $data->id }}" 
                data-model-name="{{ e($data->name) }}"
                data-delete-url="{{ route('backend.hairstyle-models.delete_images', $data->id) }}"
                data-destroy-url="{{ route('backend.hairstyle-models.destroy', $data->id) }}"
                data-images='@json($data->feature_image_items, JSON_HEX_APOS|JSON_HEX_QUOT)'
                title="Supprimer des images / le modèle">
                <i class="fa-solid fa-trash"></i>
            </button>
        @else
            <a href="{{ route('backend.hairstyle-models.destroy', $data->id) }}" 
               id="delete-hairstyle-models-{{ $data->id }}" 
               class="btn btn-soft-danger btn-sm rounded" 
               data-type="ajax" 
               data-method="DELETE" 
               data-token="{{ csrf_token() }}" 
               data-bs-toggle="tooltip" 
               title="Supprimer" 
               data-confirm="Voulez-vous vraiment supprimer ce modèle de coiffure ?">
                <i class="fa-solid fa-trash"></i>
            </a>
        @endif
    @endcan
</div>
