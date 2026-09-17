@extends('backend.layouts.app')

@section('title', $gateway->exists ? 'Modifier une passerelle' : 'Ajouter une passerelle')

@section('content')
<div class="card" style="max-width:680px; margin:auto;">
    <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-4">
            <a href="{{ route('backend.payment-gateways.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h5 class="mb-0">{{ $gateway->exists ? 'Modifier : ' . $gateway->name : 'Ajouter une passerelle de paiement' }}</h5>
        </div>

        <form method="POST"
              action="{{ $gateway->exists ? route('backend.payment-gateways.update', $gateway) : route('backend.payment-gateways.store') }}">
            @csrf
            @if($gateway->exists) @method('PUT') @endif

            {{-- Infos de base --}}
            <h6 class="text-muted mb-3">Informations générales</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nom affiché <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name"
                           value="{{ old('name', $gateway->name) }}"
                           placeholder="Ex: Orange Money Burkina" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Code unique <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code"
                           value="{{ old('code', $gateway->code) }}"
                           placeholder="Ex: orange_money_bf" required
                           {{ $gateway->exists ? 'readonly' : '' }}>
                    <small class="text-muted">Minuscules + underscores. Ne pas modifier après création.</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description (affichée dans l'app)</label>
                <input type="text" class="form-control" name="description"
                       value="{{ old('description', $gateway->description) }}"
                       placeholder="Ex: Payer avec Orange Money Burkina Faso">
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">URL du logo</label>
                    <input type="text" class="form-control" name="logo_url"
                           value="{{ old('logo_url', $gateway->logo_url) }}"
                           placeholder="/images/payment/orange_money.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Préfixes téléphoniques</label>
                    <input type="text" class="form-control" name="phone_prefixes"
                           value="{{ old('phone_prefixes', $gateway->phone_prefixes) }}"
                           placeholder="07,06,05">
                    <small class="text-muted">Séparés par des virgules</small>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Driver technique <span class="text-danger">*</span></label>
                    <select class="form-select" name="driver" required>
                        <option value="pulse_kango" {{ old('driver', $gateway->driver) == 'pulse_kango' ? 'selected' : '' }}>PulseKango</option>
                        <option value="cinetpay"    {{ old('driver', $gateway->driver) == 'cinetpay'    ? 'selected' : '' }}>CinetPay</option>
                        <option value="yennegapay"  {{ old('driver', $gateway->driver) == 'yennegapay'  ? 'selected' : '' }}>Yennegapay</option>
                        <option value="autre"       {{ !in_array(old('driver', $gateway->driver), ['pulse_kango','cinetpay','yennegapay']) ? 'selected' : '' }}>Autre…</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordre</label>
                    <input type="number" class="form-control" name="sort_order"
                           value="{{ old('sort_order', $gateway->sort_order ?? 0) }}" min="0">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $gateway->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Actif</label>
                    </div>
                </div>
            </div>

            {{-- Credentials --}}
            <h6 class="text-muted mb-3">Credentials API
                <small class="fw-normal">(laissez vide pour utiliser les valeurs du .env)</small>
            </h6>

            @php
                $config = $gateway->config ?? [];
                $fields = [
                    'api_key'     => ['label' => 'API Key',     'type' => 'password'],
                    'username'    => ['label' => 'Username',    'type' => 'text'],
                    'secret'      => ['label' => 'Secret',      'type' => 'password'],
                    'base_url'    => ['label' => 'Base URL',    'type' => 'url'],
                    'merchant_id' => ['label' => 'Merchant ID', 'type' => 'text'],
                    'site_id'     => ['label' => 'Site ID',     'type' => 'text'],
                ];
            @endphp

            <div class="row g-3 mb-4">
                @foreach($fields as $key => $field)
                <div class="col-md-6">
                    <label class="form-label">{{ $field['label'] }}</label>
                    <input type="{{ $field['type'] }}" class="form-control" name="config_{{ $key }}"
                           placeholder="{{ $gateway->exists && isset($config[$key]) ? '••••••••' : 'Optionnel' }}"
                           autocomplete="off">
                    @if($gateway->exists && isset($config[$key]))
                        <small class="text-success"><i class="fas fa-check-circle"></i> Configuré</small>
                    @endif
                </div>
                @endforeach
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    {{ $gateway->exists ? 'Mettre à jour' : 'Créer la passerelle' }}
                </button>
                <a href="{{ route('backend.payment-gateways.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>

        </form>
    </div>
</div>
@endsection
