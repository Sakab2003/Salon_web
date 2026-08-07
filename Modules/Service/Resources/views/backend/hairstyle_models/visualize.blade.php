@extends('backend.layouts.app')

@section('title')
    {{ $module_action }} {{ $module_title }}
@endsection

@push('after-styles')
<style>
    .service-model-card {
        border: none;
        border-radius: 16px;
        transition: all 0.3s ease;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }
    .dark .service-model-card {
        background: #1f2937;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    .service-model-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(112, 0, 255, 0.15);
    }
    .service-card-img-wrap {
        height: 220px;
        overflow: hidden;
        position: relative;
    }
    .service-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .service-model-card:hover .service-card-img {
        transform: scale(1.08);
    }
    .model-count-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(8px);
        color: #fff;
        font-weight: 600;
        font-size: 0.85rem;
        padding: 6px 14px;
        border-radius: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .model-gallery-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        transition: all 0.25s ease;
    }
    .dark .model-gallery-card {
        border-color: #374151;
        background: #111827;
    }
    .model-gallery-card:hover {
        border-color: #6366f1;
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
    }
    .model-gallery-img {
        height: 240px;
        width: 100%;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-scissors text-primary me-2"></i>Visualiser les Modèles de Coiffure</h3>
            <p class="text-muted m-0">Parcourez les modèles de coiffure regroupés par service prestation.</p>
        </div>
        <div class="col-md-5 mt-3 mt-md-0">
            <div class="input-group shadow-sm rounded-pill overflow-hidden">
                <span class="input-group-text bg-white dark:bg-dark border-0 ps-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="search-service-input" class="form-control border-0 py-2" placeholder="Rechercher un service ou un modèle...">
            </div>
        </div>
    </div>

    @if($services->isEmpty())
        <div class="card p-5 text-center shadow-sm rounded-4">
            <div class="my-4">
                <i class="fa-solid fa-scissors fa-4x text-muted opacity-50 mb-3"></i>
                <h4 class="fw-semibold">Aucun modèle de coiffure créé</h4>
                <p class="text-muted">Commencez par ajouter des modèles dans la section <a href="{{ route('backend.hairstyle-models.index') }}" class="fw-bold text-primary">Liste des modèles</a>.</p>
            </div>
        </div>
    @else
        <div class="row g-4" id="services-cards-container">
            @foreach($services as $service)
                @php
                    $models = $service->hairstyle_models;
                    $firstModel = $models->first();
                    $coverImage = $firstModel ? $firstModel->feature_image : $service->feature_image;
                    $categoryName = optional($service->category)->name ?? 'Coiffure';
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 service-card-item" data-search="{{ strtolower($service->name . ' ' . $categoryName . ' ' . implode(' ', $models->pluck('name')->toArray())) }}">
                    <div class="card service-model-card h-100">
                        <div class="service-card-img-wrap">
                            <img src="{{ $coverImage }}" class="service-card-img" alt="{{ $service->name }}">
                            <span class="model-count-badge">
                                <i class="fa-solid fa-layer-group me-1 text-warning"></i> {{ $models->count() }} {{ $models->count() > 1 ? 'modèles' : 'modèle' }}
                            </span>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-soft-primary rounded-pill px-3 py-1">{{ $categoryName }}</span>
                                    <small class="text-muted"><i class="fa-solid fa-tag me-1"></i>Service</small>
                                </div>
                                <h5 class="fw-bold card-title mb-2 text-dark dark:text-light">{{ $service->name }}</h5>
                                <p class="text-muted small line-clamp-2 mb-3">
                                    Découvrez les {{ $models->count() }} styles et modèles disponibles pour cette prestation de coiffure.
                                </p>
                            </div>
                            <button type="button" 
                                class="btn btn-primary rounded-pill w-100 mt-2 btn-explore-service" 
                                data-bs-toggle="modal" 
                                data-bs-target="#service-models-modal-{{ $service->id }}">
                                <i class="fa-solid fa-eye me-2"></i> Explorer les modèles
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal avec la galerie des modèles du service -->
                <div class="modal fade" id="service-models-modal-{{ $service->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <span class="badge bg-soft-primary rounded-pill mb-1">{{ $categoryName }}</span>
                                    <h4 class="modal-title fw-bold">{{ $service->name }}</h4>
                                    <p class="text-muted small m-0">{{ $models->count() }} {{ $models->count() > 1 ? 'modèles créés pour ce service' : 'modèle créé pour ce service' }}</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-4">
                                    @foreach($models as $model)
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="card model-gallery-card h-100">
                                                <img src="{{ $model->feature_image }}" class="model-gallery-img" alt="{{ $model->name }}">
                                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                                    <div>
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="fw-bold m-0 text-primary">{{ $model->name }}</h6>
                                                            @if($model->status == 1)
                                                                <span class="badge bg-soft-success">Public</span>
                                                            @else
                                                                <span class="badge bg-soft-secondary">Privé</span>
                                                            @endif
                                                        </div>
                                                        @if($model->description)
                                                            <p class="text-muted small mb-2">{{ $model->description }}</p>
                                                        @else
                                                            <p class="text-muted small fst-italic mb-2">Aucune description renseignée.</p>
                                                        @endif
                                                    </div>
                                                    <div class="pt-2 border-top mt-2 d-flex justify-content-between align-items-center">
                                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>{{ $model->created_at ? $model->created_at->isoFormat('D MMM YYYY') : '-' }}</small>
                                                        <span class="badge bg-soft-dark text-dark small"><i class="fa-solid fa-scissors me-1"></i>{{ $service->name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Fermer</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('search-service-input');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const items = document.querySelectorAll('.service-card-item');
                
                items.forEach(item => {
                    const searchData = item.getAttribute('data-search') || '';
                    if (searchData.includes(query)) {
                        item.style.display = '';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endpush
