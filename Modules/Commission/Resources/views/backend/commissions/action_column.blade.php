<div class="d-flex align-items-center justify-content-end gap-2">
    @can('edit_commission')
    <a href="{{ route('backend.commissions.edit', $data->id) }}" class="btn btn-sm btn-icon btn-soft-primary" data-bs-toggle="tooltip" title="Modifier">
        <i class="fa-solid fa-pen"></i>
    </a>
    @endcan
    @can('delete_commission')
    <form action="{{ route('backend.commissions.destroy', $data->id) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer cette commission ?');" style="display:inline-block;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-icon btn-soft-danger" data-bs-toggle="tooltip" title="Supprimer">
            <i class="fa-solid fa-trash"></i>
        </button>
    </form>
    @endcan
</div>
