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
        <a href="{{ route('backend.hairstyle-models.destroy', $data->id) }}" 
           class="btn btn-soft-danger btn-sm rounded" 
           data-form="delete" 
           data-modal="delete" 
           data-datatable="reload" 
           title="Supprimer">
            <i class="fa-solid fa-trash"></i>
        </a>
    @endcan
</div>
