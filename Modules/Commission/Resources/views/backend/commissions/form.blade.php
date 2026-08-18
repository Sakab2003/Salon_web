<div class="row mb-3">
    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label for="title" class="form-label">Titre de la commission <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title" class="form-control" placeholder="ex: Commission de coiffure" value="{{ old('title', $data->title ?? '') }}" required>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="form-group">
            <label for="commission_type" class="form-label">Type de commission <span class="text-danger">*</span></label>
            <select name="commission_type" id="commission_type" class="form-control select2" required>
                <option value="percentage" {{ old('commission_type', $data->commission_type ?? '') == 'percentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                <option value="fixed" {{ old('commission_type', $data->commission_type ?? '') == 'fixed' ? 'selected' : '' }}>Montant fixe</option>
            </select>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="form-group">
            <label for="commission_value" class="form-label">Valeur <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="commission_value" id="commission_value" class="form-control" placeholder="10" value="{{ old('commission_value', $data->commission_value ?? '') }}" required>
        </div>
    </div>

    <div class="col-md-12 mb-3">
        <div class="form-group">
            <label for="branches" class="form-label">Salons concernés (laisser vide pour appliquer à TOUS les salons)</label>
            @php
                $selectedBranches = isset($data) ? $data->branches->pluck('id')->toArray() : [];
            @endphp
            <select name="branches[]" id="branches" class="form-control select2" multiple style="width: 100%;">
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ in_array($b->id, old('branches', $selectedBranches)) ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Si aucun salon n'est sélectionné, la commission est imposée et accessible dans l'ensemble des salons.</small>
        </div>
    </div>
</div>
