@extends('backend.layouts.app')

@section('title', 'Passerelles de paiement')

@push('after-styles')
<style>
.gateway-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: box-shadow 0.2s;
}
.gateway-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.10); }
.gateway-logo { width: 56px; height: 56px; object-fit: cover; border-radius: 8px; }
.driver-badge { font-size: 11px; background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 99px; }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-body">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">💳 Passerelles de paiement</h4>
                <p class="text-muted mb-0">Gérez les moyens de paiement Mobile Money disponibles dans l'application mobile.</p>
            </div>
            <a href="{{ route('backend.payment-gateways.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Ajouter un moyen de paiement
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Gateway Cards --}}
        <div class="row g-3">
            @forelse($gateways as $gateway)
            <div class="col-md-6 col-lg-4">
                <div class="gateway-card p-3 h-100 d-flex flex-column">

                    <div class="d-flex align-items-center gap-3 mb-3">
                        @if($gateway->logo_url)
                            <img src="{{ url($gateway->logo_url) }}" alt="{{ $gateway->name }}" class="gateway-logo">
                        @else
                            <div class="gateway-logo bg-secondary d-flex align-items-center justify-content-center rounded text-white fw-bold">
                                {{ strtoupper(substr($gateway->name, 0, 2)) }}
                            </div>
                        @endif
                        <div>
                            <h6 class="mb-1">{{ $gateway->name }}</h6>
                            <span class="driver-badge">{{ $gateway->driver }}</span>
                        </div>
                    </div>

                    <p class="text-muted small mb-2">{{ $gateway->description ?? '—' }}</p>

                    @if($gateway->phone_prefixes)
                    <p class="small mb-2">
                        <strong>Préfixes :</strong>
                        @foreach(explode(',', $gateway->phone_prefixes) as $prefix)
                            <code>{{ trim($prefix) }}</code>
                        @endforeach
                    </p>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input gateway-toggle"
                                   type="checkbox"
                                   data-id="{{ $gateway->id }}"
                                   data-url="{{ route('backend.payment-gateways.toggle', $gateway) }}"
                                   {{ $gateway->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small">
                                {{ $gateway->is_active ? 'Actif' : 'Inactif' }}
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('backend.payment-gateways.edit', $gateway) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('backend.payment-gateways.destroy', $gateway) }}"
                                  onsubmit="return confirm('Supprimer cette passerelle ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucune passerelle configurée. Ajoutez Orange Money, Moov Money, Yennegapay…</p>
                    <a href="{{ route('backend.payment-gateways.create') }}" class="btn btn-primary">Ajouter maintenant</a>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Prix des plans --}}
        <hr class="my-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">💰 Prix des abonnements</h5>
                <p class="text-muted small mb-0">Ces prix s'affichent dans l'application mobile. Modifiez-les ici sans toucher au code.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('backend.payment-gateways.update-prices') }}">
            @csrf
            <div class="row g-3">
                @foreach(\Modules\Subscriptions\Models\Plan::whereIn('identifier', ['monthly','yearly'])->get() as $plan)
                <div class="col-md-6">
                    <div class="gateway-card p-3">
                        <label class="form-label fw-semibold">{{ $plan->name }}</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="prices[{{ $plan->id }}]"
                                   value="{{ $plan->amount }}" min="1">
                            <span class="input-group-text">FCFA</span>
                        </div>
                        <small class="text-muted">{{ $plan->duration }} jours</small>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Enregistrer les prix
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

@push('after-scripts')
<script>
document.querySelectorAll('.gateway-toggle').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
        const url = this.dataset.url;
        const label = this.nextElementSibling;
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => { label.textContent = data.is_active ? 'Actif' : 'Inactif'; });
    });
});
</script>
@endpush
