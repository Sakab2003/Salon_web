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
        cursor: pointer;
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
        background: rgba(15, 23, 42, 0.85);
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
        cursor: pointer;
        position: relative;
    }
    .dark .model-gallery-card {
        border-color: #374151;
        background: #111827;
    }
    .model-gallery-card:hover {
        border-color: #6366f1;
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        transform: translateY(-4px);
    }
    .model-gallery-img-wrap {
        position: relative;
        height: 240px;
        overflow: hidden;
    }
    .model-gallery-img {
        height: 100%;
        width: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .model-gallery-card:hover .model-gallery-img {
        transform: scale(1.06);
    }
    .zoom-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        color: #fff;
        font-size: 1.4rem;
    }
    .model-gallery-card:hover .zoom-overlay {
        opacity: 1;
    }

    /* Lightbox Plein Écran (Style Google Images) */
    .lightbox-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .lightbox-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    .lightbox-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(10, 15, 29, 0.94);
        backdrop-filter: blur(14px);
    }
    .lightbox-container {
        position: relative;
        z-index: 2;
        display: flex;
        max-width: 92vw;
        max-height: 88vh;
        width: 1100px;
        background: #111827;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.12);
    }
    @media (max-width: 991.98px) {
        .lightbox-container {
            flex-direction: column;
            max-height: 90vh;
            overflow-y: auto;
        }
    }
    .lightbox-image-wrap {
        flex: 1 1 65%;
        background: #030712;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        min-height: 380px;
    }
    .lightbox-image-wrap img {
        max-width: 100%;
        max-height: 76vh;
        object-fit: contain;
        border-radius: 14px;
        box-shadow: 0 12px 36px rgba(0,0,0,0.6);
        transition: transform 0.3s ease;
    }
    .lightbox-details-panel {
        flex: 1 1 35%;
        padding: 32px;
        display: flex;
        flex-direction: column;
        color: #f8fafc;
        background: #1f2937;
        border-left: 1px solid rgba(255, 255, 255, 0.08);
    }
    .lightbox-close-btn {
        position: absolute;
        top: 20px;
        right: 24px;
        z-index: 100005;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        font-size: 1.8rem;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.25s ease;
        line-height: 1;
    }
    .lightbox-close-btn:hover {
        background: rgba(239, 68, 68, 0.85);
        border-color: rgba(239, 68, 68, 1);
        transform: scale(1.1);
    }
    .lightbox-nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 100005;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        font-size: 1.3rem;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .lightbox-nav-btn:hover {
        background: rgba(99, 102, 241, 0.85);
        border-color: rgba(99, 102, 241, 1);
        transform: translateY(-50%) scale(1.1);
    }
    .lightbox-prev {
        left: 24px;
    }
    .lightbox-next {
        right: 24px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-5">
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-layer-group text-primary me-2"></i>Modèles de service</h3>
        </div>
        <div class="col-md-7 mt-3 mt-md-0 d-flex flex-wrap gap-2 justify-content-md-end">
            <div style="min-width: 200px;">
                <select id="filter-service-select" class="form-select shadow-sm rounded-pill py-2">
                    <option value="">Tous les services</option>
                    @foreach($services as $s)
                        <option value="{{ strtolower($s->name) }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input-group shadow-sm rounded-pill overflow-hidden flex-grow-1" style="max-width: 320px;">
                <span class="input-group-text bg-white dark:bg-dark border-0 ps-3"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" id="search-service-input" class="form-control border-0 py-2" placeholder="Rechercher un service ou un modèle...">
            </div>
        </div>
    </div>

    @if($services->isEmpty())
        <div class="card p-5 text-center shadow-sm rounded-4">
            <div class="my-4">
                <i class="fa-solid fa-layer-group fa-4x text-muted opacity-50 mb-3"></i>
                <h4 class="fw-semibold">Aucun modèle disponible</h4>
                <p class="text-muted">Commencez par ajouter des modèles dans la section <a href="{{ route('backend.hairstyle-models.index') }}" class="fw-bold text-primary">Liste des modèles</a>.</p>
            </div>
    @else
        <div class="row g-4" id="services-cards-container">
            @foreach($services as $service)
                @php
                    $models = $service->hairstyle_models;
                    $flatItems = collect();

                    foreach($models as $model) {
                        $images = $model->feature_images;
                        if (empty($images)) {
                            $images = [$model->feature_image];
                        }
                        $totalImgCount = count($images);
                        foreach($images as $imgIdx => $imgUrl) {
                            $flatItems->push((object)[
                                'model_id' => $model->id,
                                'model_name' => $model->name,
                                'description' => $model->description,
                                'status' => $model->status,
                                'created_at' => $model->created_at,
                                'image' => $imgUrl,
                                'img_index' => $imgIdx + 1,
                                'total_img' => $totalImgCount,
                            ]);
                        }
                    }

                    $firstItem = $flatItems->first();
                    $coverImage = !empty($service->feature_image) ? $service->feature_image : ($firstItem ? $firstItem->image : asset('dummy-images/common/Service 8.webp'));
                    $serviceName = $service->name;
                    $sNameLower = strtolower($serviceName);

                    // Dynamic icon selection per service type
                    $serviceIcon = 'fa-scissors';
                    if (str_contains($sNameLower, 'massage') || str_contains($sNameLower, 'détente') || str_contains($sNameLower, 'relax')) {
                        $serviceIcon = 'fa-spa';
                    } elseif (str_contains($sNameLower, 'maquillage') || str_contains($sNameLower, 'makeup') || str_contains($sNameLower, 'visage')) {
                        $serviceIcon = 'fa-wand-magic-sparkles';
                    } elseif (str_contains($sNameLower, 'ongle') || str_contains($sNameLower, 'manucure') || str_contains($sNameLower, 'pédicure')) {
                        $serviceIcon = 'fa-hand-sparkles';
                    } elseif (str_contains($sNameLower, 'soin') || str_contains($sNameLower, 'peau')) {
                        $serviceIcon = 'fa-heart-pulse';
                    }

                    $totalPhotosCount = $flatItems->count();
                @endphp
                <div class="col-12 col-sm-6 col-lg-4 service-card-item" data-search="{{ strtolower($serviceName . ' ' . implode(' ', $flatItems->pluck('model_name')->toArray())) }}">
                    <div class="card service-model-card h-100">
                        <div class="service-card-img-wrap" role="button" data-bs-toggle="modal" data-bs-target="#service-models-modal-{{ $service->id }}">
                            <img src="{{ $coverImage }}" class="service-card-img" alt="{{ $serviceName }}">
                            @if($models->count() > 0)
                            <span class="model-count-badge">
                                <i class="fa-solid fa-scissors me-1 text-warning"></i> {{ $models->count() }} {{ $models->count() > 1 ? 'modèles' : 'modèle' }}
                            </span>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between p-4">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-soft-primary rounded-pill px-3 py-1">Service</span>
                                    <small class="text-muted"><i class="fa-solid {{ $serviceIcon }} me-1"></i>{{ $serviceName }}</small>
                                </div>
                                <h5 class="fw-bold card-title mb-2 text-dark dark:text-light">{{ $serviceName }}</h5>
                                <p class="text-muted small line-clamp-2 mb-3">
                                    {{ $models->count() }} {{ $models->count() > 1 ? 'modèles disponibles' : 'modèle disponible' }} pour ce service.
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

                <!-- Modal avec la galerie complète des photos du service -->
                <div class="modal fade" id="service-models-modal-{{ $service->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <span class="badge bg-soft-primary rounded-pill mb-1">Service</span>
                                    <h4 class="modal-title fw-bold">{{ $serviceName }}</h4>
                                    <p class="text-muted small m-0">{{ $models->count() }} {{ $models->count() > 1 ? 'modèles disponibles' : 'modèle disponible' }} pour ce service</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="row g-4">
                                    @foreach($flatItems as $globalIdx => $item)
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="card model-gallery-card h-100 btn-open-lightbox" 
                                                 data-service-id="{{ $service->id }}"
                                                 data-global-index="{{ $globalIdx }}"
                                                 data-service-photos='@json($flatItems, JSON_HEX_APOS|JSON_HEX_QUOT)'
                                                 data-service-name="{{ $serviceName }}">
                                                
                                                <div class="model-gallery-img-wrap">
                                                    <img src="{{ $item->image }}" class="model-gallery-img" alt="{{ $item->model_name }}">
                                                    @if($item->total_img > 1)
                                                        <span class="model-count-badge" style="top: 12px; left: 12px; right: auto; background: rgba(99, 102, 241, 0.9); font-size: 0.75rem;">
                                                            <i class="fa-regular fa-image me-1"></i>Photo {{ $item->img_index }}/{{ $item->total_img }}
                                                        </span>
                                                    @endif
                                                    <div class="zoom-overlay">
                                                        <i class="fa-solid fa-magnifying-glass-plus me-2"></i> Voir grand format
                                                    </div>
                                                </div>
                                                <div class="card-body p-3 d-flex flex-column justify-content-between">
                                                    <div>
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <h6 class="fw-bold m-0 text-primary">{{ $item->model_name }}</h6>
                                                            @if($item->status == 1)
                                                                <span class="badge bg-soft-success">Public</span>
                                                            @else
                                                                <span class="badge bg-soft-secondary">Privé</span>
                                                            @endif
                                                        </div>
                                                        @if($item->description)
                                                            <p class="text-muted small mb-2 line-clamp-2">{{ $item->description }}</p>
                                                        @else
                                                            <p class="text-muted small fst-italic mb-2">Aucune description renseignée.</p>
                                                        @endif
                                                    </div>
                                                    <div class="pt-2 border-top mt-2 d-flex justify-content-between align-items-center">
                                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i>{{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->isoFormat('D MMM YYYY') : '-' }}</small>
                                                        <span class="badge bg-soft-dark text-dark small"><i class="fa-solid fa-scissors me-1"></i>{{ $serviceName }}</span>
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

<!-- Lightbox Plein Écran (Style Google Images) -->
<div id="image-lightbox" class="lightbox-overlay">
    <div class="lightbox-backdrop" id="lightbox-backdrop"></div>
    <button type="button" class="lightbox-close-btn" id="lightbox-close" title="Fermer (Échap)">&times;</button>
    
    <button type="button" class="lightbox-nav-btn lightbox-prev" id="lightbox-prev" title="Précédent (Flèche gauche)">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    <button type="button" class="lightbox-nav-btn lightbox-next" id="lightbox-next" title="Suivant (Flèche droite)">
        <i class="fa-solid fa-chevron-right"></i>
    </button>

    <div class="lightbox-container">
        <div class="lightbox-image-wrap">
            <img id="lightbox-img" src="" alt="Aperçu grand format">
        </div>
        <div class="lightbox-details-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill fw-semibold" id="lightbox-service-name"><i class="fa-solid fa-scissors me-1"></i>Service</span>
                <span class="badge px-3 py-2 rounded-pill fw-semibold" id="lightbox-status">Statut</span>
            </div>
            <h3 class="fw-bold text-white mb-2" id="lightbox-title">Nom du Modèle</h3>
            <p class="text-info small fw-bold mb-3 d-none" id="lightbox-photo-index"></p>
            <div class="lightbox-desc-box mb-4">
                <label class="text-muted small uppercase fw-bold mb-1 d-block">Description / Conseils de style :</label>
                <p class="text-light opacity-90 m-0" id="lightbox-desc">Description</p>
            </div>
            <div class="mt-auto pt-3 border-top border-secondary text-muted small d-flex justify-content-between align-items-center">
                <span><i class="fa-regular fa-calendar me-1"></i><span id="lightbox-date"></span></span>
                <span class="badge bg-dark text-light px-3 py-2 rounded-pill" id="lightbox-counter">1 / 1</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Recherche et filtre par service en direct
        const searchInput = document.getElementById('search-service-input');
        const serviceSelect = document.getElementById('filter-service-select');
        
        function applyCombinedFilter() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedSvc = serviceSelect ? serviceSelect.value.toLowerCase().trim() : '';
            const items = document.querySelectorAll('.service-card-item');

            items.forEach(item => {
                const searchData = item.getAttribute('data-search') || '';
                const matchesQuery = !query || searchData.includes(query);
                const matchesSvc = !selectedSvc || searchData.includes(selectedSvc);

                if (matchesQuery && matchesSvc) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', applyCombinedFilter);
        if (serviceSelect) serviceSelect.addEventListener('change', applyCombinedFilter);

        // Lightbox Logic
        const lightbox = document.getElementById('image-lightbox');
        const lightboxImg = document.getElementById('lightbox-img');
        const lightboxTitle = document.getElementById('lightbox-title');
        const lightboxPhotoIndex = document.getElementById('lightbox-photo-index');
        const lightboxDesc = document.getElementById('lightbox-desc');
        const lightboxServiceName = document.getElementById('lightbox-service-name');
        const lightboxStatus = document.getElementById('lightbox-status');
        const lightboxDate = document.getElementById('lightbox-date');
        const lightboxCounter = document.getElementById('lightbox-counter');
        const closeBtn = document.getElementById('lightbox-close');
        const backdrop = document.getElementById('lightbox-backdrop');
        const prevBtn = document.getElementById('lightbox-prev');
        const nextBtn = document.getElementById('lightbox-next');

        let currentServicePhotos = [];
        let currentGlobalIndex = 0;
        let currentServiceName = '';

        function updateLightboxView() {
            if (!currentServicePhotos || currentServicePhotos.length === 0) return;
            
            if (currentGlobalIndex < 0) currentGlobalIndex = currentServicePhotos.length - 1;
            if (currentGlobalIndex >= currentServicePhotos.length) currentGlobalIndex = 0;

            const item = currentServicePhotos[currentGlobalIndex];
            
            lightboxImg.src = item.image;
            lightboxTitle.textContent = item.model_name;
            lightboxDesc.textContent = item.description || 'Aucune description renseignée.';
            lightboxServiceName.innerHTML = `<i class="fa-solid fa-scissors me-1"></i>${currentServiceName}`;
            
            if (item.created_at) {
                const d = new Date(item.created_at);
                lightboxDate.textContent = isNaN(d.getTime()) ? item.created_at : d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
            } else {
                lightboxDate.textContent = '-';
            }

            lightboxCounter.textContent = `${currentGlobalIndex + 1} / ${currentServicePhotos.length} Photos`;

            if (item.total_img && item.total_img > 1) {
                lightboxPhotoIndex.textContent = `Photo ${item.img_index} sur ${item.total_img} (Modèle: ${item.model_name})`;
                lightboxPhotoIndex.classList.remove('d-none');
            } else {
                lightboxPhotoIndex.classList.add('d-none');
            }

            if (item.status === 1 || item.status === '1') {
                lightboxStatus.className = 'badge bg-soft-success text-success px-3 py-2 rounded-pill fw-semibold';
                lightboxStatus.textContent = 'Public';
            } else {
                lightboxStatus.className = 'badge bg-soft-secondary text-secondary px-3 py-2 rounded-pill fw-semibold';
                lightboxStatus.textContent = 'Privé';
            }

            if (currentServicePhotos.length <= 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
            } else {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
            }
        }

        function openLightbox(photosList, globalIndex, serviceName) {
            currentServicePhotos = photosList;
            currentGlobalIndex = globalIndex;
            currentServiceName = serviceName;
            updateLightboxView();
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }

        function prevPhoto() {
            if (currentServicePhotos.length === 0) return;
            currentGlobalIndex--;
            updateLightboxView();
        }

        function nextPhoto() {
            if (currentServicePhotos.length === 0) return;
            currentGlobalIndex++;
            updateLightboxView();
        }

        // Attach Click to all model gallery cards
        document.querySelectorAll('.btn-open-lightbox').forEach(card => {
            card.addEventListener('click', function(e) {
                const photosDataStr = this.getAttribute('data-service-photos');
                const serviceName = this.getAttribute('data-service-name') || '';
                const globalIndex = parseInt(this.getAttribute('data-global-index') || 0);

                let photosList = [];
                try {
                    photosList = JSON.parse(photosDataStr);
                } catch (err) {
                    photosList = [];
                }

                if (photosList.length > 0) {
                    openLightbox(photosList, globalIndex, serviceName);
                }
            });
        });

        // Event listeners
        if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
        if (backdrop) backdrop.addEventListener('click', closeLightbox);
        if (prevBtn) prevBtn.addEventListener('click', prevPhoto);
        if (nextBtn) nextBtn.addEventListener('click', nextPhoto);

        // Keyboard controls
        document.addEventListener('keydown', (e) => {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevPhoto();
            if (e.key === 'ArrowRight') nextPhoto();
        });
    });
</script>
@endpush
