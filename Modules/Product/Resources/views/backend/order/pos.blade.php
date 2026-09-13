@extends('backend.layouts.app')

@section('title')
    {{ __('Vente Directe (POS)') }}
@endsection

@push('after-styles')
<style>
    .pos-item-card {
        cursor: pointer;
        transition: all 0.25s ease-in-out;
        border: 2px solid transparent;
        border-radius: 16px;
        overflow: hidden;
    }
    .pos-item-card:hover:not(.out-of-stock) {
        transform: translateY(-4px);
        border-color: var(--bs-primary);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    }
    .pos-item-card.out-of-stock {
        opacity: 0.55;
        cursor: not-allowed;
        filter: grayscale(80%);
    }
    .pos-cart-panel {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 80px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    .cart-items-container {
        max-height: 240px;
        overflow-y: auto;
    }
    .qty-btn {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        padding: 0;
    }
    .payment-method-pill {
        cursor: pointer;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px 14px;
        transition: all 0.2s;
        text-align: center;
    }
    .payment-method-pill.active {
        border-color: var(--bs-primary);
        background-color: rgba(var(--bs-primary-rgb), 0.08);
        color: var(--bs-primary);
        font-weight: bold;
    }
    .quick-cash-btn {
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
    }
    .pos-filter-pill {
        cursor: pointer;
        padding: 8px 18px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
    }
    .pos-filter-pill.active {
        background: #6f42c1;
        color: #ffffff;
        border-color: #6f42c1;
        box-shadow: 0 4px 12px rgba(111, 66, 193, 0.25);
    }
    .btn-purple-custom {
        background-color: #6f42c1 !important;
        border-color: #6f42c1 !important;
        color: #ffffff !important;
    }
    .btn-purple-custom:hover {
        background-color: #5a32a3 !important;
        border-color: #5a32a3 !important;
        color: #ffffff !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="row g-4">
        <!-- Colonne Gauche : Catalogue Produits & Services -->
        <div class="col-lg-7 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h4 class="fw-bold mb-1"><i class="fa-solid fa-cash-register text-primary me-2"></i> Terminal de Vente (POS)</h4>
                            <p class="text-muted small mb-0">Enregistrez un achat client (Produits et/ou Prestations de service)</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-6">
                                <i class="fa-solid fa-boxes-stacked me-1"></i> <span id="total-catalogue-count">{{ count($products) + count($services ?? []) }}</span> Éléments
                            </span>
                        </div>
                    </div>

                    <!-- Filtres Produits / Services / Tous -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="pos-filter-pill active" onclick="filterCatalog('all', this)">
                            <i class="fa-solid fa-layer-group me-1"></i> Tous ({{ count($products) + count($services ?? []) }})
                        </button>
                        <button type="button" class="pos-filter-pill" onclick="filterCatalog('product', this)">
                            <i class="fa-solid fa-box-open me-1"></i> Produits ({{ count($products) }})
                        </button>
                        <button type="button" class="pos-filter-pill" onclick="filterCatalog('service', this)">
                            <i class="fa-solid fa-scissors me-1"></i> Services ({{ count($services ?? []) }})
                        </button>
                    </div>

                    <!-- Barre de Recherche -->
                    <div class="position-relative mb-4">
                        <span class="position-absolute top-50 translate-middle-y ms-3 text-muted">
                            <i class="fa-solid fa-magnifying-glass fs-5"></i>
                        </span>
                        <input type="text" id="pos-search-input" class="form-control form-control-lg rounded-pill ps-5 bg-light border-0" placeholder="Rechercher un produit ou une prestation par nom, référence ou prix..." autocomplete="off">
                    </div>

                    <!-- Grille des Produits & Services -->
                    <div class="row g-3" id="pos-catalog-grid">
                        <!-- Produits -->
                        @foreach($products as $product)
                            @php
                                $stock = (int) $product->stock_qty;
                                $price = (float) ($product->max_price ?: $product->min_price);
                                $isOut = $stock <= 0;
                                $img = $product->feature_image ?: 'https://dummyimage.com/300x300/e2e8f0/64748b.png&text=Produit';
                            @endphp
                            <div class="col-sm-6 col-md-4 col-xl-3 pos-catalog-item" data-type="product" data-name="{{ strtolower($product->name) }}" data-price="{{ $price }}">
                                <div class="card h-100 pos-item-card shadow-sm {{ $isOut ? 'out-of-stock' : '' }}" 
                                     onclick="addToCart('product', {{ $product->id }}, '{{ addslashes($product->name) }}', {{ $price }}, {{ $stock }}, '{{ $img }}')">
                                    <div class="position-relative">
                                        <img src="{{ $img }}" class="card-img-top" alt="{{ $product->name }}" style="height: 120px; object-fit: cover;">
                                        @if($isOut)
                                            <span class="position-absolute top-0 start-0 m-2 badge bg-danger rounded-pill">
                                                <i class="fa-solid fa-ban me-1"></i> Rupture
                                            </span>
                                        @elseif($stock <= 5)
                                            <span class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark rounded-pill">
                                                Stock faible ({{ $stock }})
                                            </span>
                                        @else
                                            <span class="position-absolute top-0 start-0 m-2 badge bg-success rounded-pill">
                                                {{ $stock }} en stock
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                                        <div>
                                            <span class="badge bg-secondary-subtle text-secondary mb-1" style="font-size: 0.7rem;">Produit</span>
                                            <h6 class="fw-bold mb-1 text-truncate" title="{{ $product->name }}">{{ $product->name }}</h6>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="fw-bolder text-primary fs-6">{{ \Currency::format($price) }}</span>
                                            <button class="btn btn-sm btn-outline-primary rounded-circle p-1" style="width: 28px; height: 28px;" {{ $isOut ? 'disabled' : '' }}>
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- Services -->
                        @foreach($services ?? [] as $srv)
                            @php
                                $price = (float) $srv->default_price;
                                $img = $srv->feature_image ?: 'https://dummyimage.com/300x300/f1f5f9/64748b.png&text=Service';
                            @endphp
                            <div class="col-sm-6 col-md-4 col-xl-3 pos-catalog-item" data-type="service" data-name="{{ strtolower($srv->name) }}" data-price="{{ $price }}">
                                <div class="card h-100 pos-item-card shadow-sm" 
                                     onclick="addToCart('service', {{ $srv->id }}, '{{ addslashes($srv->name) }}', {{ $price }}, 9999, '{{ $img }}')">
                                    <div class="position-relative">
                                        <img src="{{ $img }}" class="card-img-top" alt="{{ $srv->name }}" style="height: 120px; object-fit: cover;">
                                        <span class="position-absolute top-0 start-0 m-2 badge bg-primary rounded-pill">
                                            <i class="fa-solid fa-scissors me-1"></i> Prestation
                                        </span>
                                    </div>
                                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                                        <div>
                                            <span class="badge bg-info-subtle text-info mb-1" style="font-size: 0.7rem;">{{ $srv->duration_min ?? 30 }} Min</span>
                                            <h6 class="fw-bold mb-1 text-truncate" title="{{ $srv->name }}">{{ $srv->name }}</h6>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="fw-bolder text-primary fs-6">{{ \Currency::format($price) }}</span>
                                            <button class="btn btn-sm btn-outline-primary rounded-circle p-1" style="width: 28px; height: 28px;">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Panier & Encaissement -->
        <div class="col-lg-5 col-xl-4">
            <div class="pos-cart-panel p-4">
                <!-- En-tête Panier -->
                <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-shopping-cart text-primary me-2"></i> Panier Client</h5>
                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="clearCart()" id="btn-clear-cart" style="display: none;">
                        <i class="fa-solid fa-trash me-1"></i> Vider
                    </button>
                </div>

                <!-- Informations Client -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold mb-0 text-dark">Informations Client</label>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
                            <i class="fa-solid fa-user-plus me-1"></i> + Nouveau
                        </button>
                    </div>
                    <select id="pos-customer-select" class="form-select rounded-3 py-2">
                        <option value="">Client de passage</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->first_name }} {{ $customer->last_name }} ({{ $customer->mobile ?: $customer->email }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Liste des Articles du Panier -->
                <div class="cart-items-container mb-3" id="cart-items-list">
                    <div class="text-center py-4 text-muted" id="empty-cart-msg">
                        <i class="fa-solid fa-cart-arrow-down fs-2 mb-2 text-secondary opacity-50"></i>
                        <p class="small mb-0">Le panier est vide. Tapez sur un élément pour l'ajouter.</p>
                    </div>
                </div>

                <!-- Récapitulatif Total -->
                <div class="p-3 bg-light rounded-4 mb-3 border">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Nombre d'articles :</span>
                        <span class="fw-bold" id="cart-total-qty">0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="fw-bold fs-6">Total à Payer :</span>
                        <span class="fw-bolder fs-4 text-primary" id="cart-total-amount">0 FCFA</span>
                    </div>
                </div>

                <!-- Mode de Paiement -->
                <div class="mb-3">
                    <label class="form-label small fw-bold mb-2">Mode de paiement</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="payment-method-pill active" data-method="Paiement Cash" onclick="setPaymentMethod(this, 'Paiement Cash')">
                                <i class="fa-solid fa-money-bill-1-wave me-1"></i> Cash
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="payment-method-pill" data-method="Orange Money" onclick="setPaymentMethod(this, 'Orange Money')">
                                <i class="fa-solid fa-mobile-screen-button me-1"></i> Orange Money
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="payment-method-pill" data-method="Moov Money" onclick="setPaymentMethod(this, 'Moov Money')">
                                <i class="fa-solid fa-signal me-1"></i> Moov Money
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="payment-method-pill" data-method="Wave" onclick="setPaymentMethod(this, 'Wave')">
                                <i class="fa-solid fa-water me-1"></i> Wave
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Montant Reçu & Monnaie à rendre -->
                <div class="mb-4">
                    <label class="form-label small fw-bold mb-1">Montant Reçu (FCFA)</label>
                    <input type="number" id="pos-amount-paid" class="form-control form-control-lg rounded-3 fw-bold text-success mb-2" placeholder="0" oninput="calculateChange()">
                    
                    <!-- Boutons d'appoint rapide -->
                    <div class="d-flex gap-1 mb-2">
                        <button type="button" class="btn btn-sm btn-light border flex-fill quick-cash-btn" onclick="setExactAmount()">Exact</button>
                        <button type="button" class="btn btn-sm btn-light border flex-fill quick-cash-btn" onclick="addCash(1000)">+1 000</button>
                        <button type="button" class="btn btn-sm btn-light border flex-fill quick-cash-btn" onclick="addCash(2000)">+2 000</button>
                        <button type="button" class="btn btn-sm btn-light border flex-fill quick-cash-btn" onclick="addCash(5000)">+5 000</button>
                        <button type="button" class="btn btn-sm btn-light border flex-fill quick-cash-btn" onclick="addCash(10000)">+10 000</button>
                    </div>

                    <!-- Affichage de la monnaie -->
                    <div id="change-display-box" class="p-2 rounded-3 text-center fw-bold d-none">
                        <span id="change-label">Monnaie à rendre :</span> <span id="change-amount-val">0 FCFA</span>
                    </div>
                </div>

                <!-- Bouton Valider -->
                <button type="button" id="btn-validate-sale" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow" onclick="submitPosSale()" disabled>
                    <i class="fa-solid fa-check-circle me-2"></i> Valider la Vente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouveau Client (Exactement identique à la capture de l'application mobile) -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; background: #faf5fa;">
            <div class="modal-body p-4 text-center">
                <h4 class="fw-bolder mb-4 text-dark" style="font-weight: 800;">Nouveau Client</h4>
                
                <div class="mb-3">
                    <input type="text" id="quick-cust-fullname" class="form-control form-control-lg bg-white border" placeholder="Nom complet" style="border-radius: 12px; height: 52px;" required>
                </div>
                
                <div class="mb-4">
                    <input type="tel" id="quick-cust-phone" class="form-control form-control-lg bg-white border" placeholder="Téléphone" style="border-radius: 12px; height: 52px;" required>
                </div>

                <button type="button" class="btn btn-purple-custom w-100 fw-bold fs-5 shadow" style="border-radius: 14px; height: 52px;" onclick="saveQuickCustomer()">
                    Ajouter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Succès de Vente & Impression -->
<div class="modal fade" id="saleSuccessModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow text-center p-4">
            <div class="mb-3">
                <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success">
                    <i class="fa-solid fa-check fs-1"></i>
                </div>
            </div>
            <h4 class="fw-bold text-success mb-1">Vente Enregistrée !</h4>
            <p class="text-muted small mb-3">La vente a été enregistrée avec succès dans l'historique des ventes.</p>

            <div class="p-3 bg-light rounded-4 mb-4 text-start">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Numéro de Commande :</span>
                    <span class="fw-bold" id="success-order-code">#000000</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Payé :</span>
                    <span class="fw-bold text-primary" id="success-total-paid">0 FCFA</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Monnaie Rendue :</span>
                    <span class="fw-bold text-success" id="success-change">0 FCFA</span>
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <a href="javascript:void(0)" id="btn-print-invoice" target="_blank" class="btn btn-primary rounded-pill py-2 fw-bold">
                    <i class="fa-solid fa-print me-1"></i> Voir / Imprimer la Facture
                </a>
                <button type="button" class="btn btn-outline-secondary rounded-pill py-2" data-bs-dismiss="modal" onclick="resetPos()">
                    <i class="fa-solid fa-plus me-1"></i> Nouvelle Vente
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
    let cart = [];
    let selectedPaymentMethod = 'Paiement Cash';
    let currentCatalogFilter = 'all';

    // Bip audio
    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(800, ctx.currentTime);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.08);
        } catch(e) {}
    }

    // Filtrer le catalogue par type (Tous, Produits, Services)
    function filterCatalog(type, btn) {
        currentCatalogFilter = type;
        document.querySelectorAll('.pos-filter-pill').forEach(el => el.classList.remove('active'));
        if (btn) btn.classList.add('active');
        applyFilters();
    }

    // Filtrer les articles (Recherche + Type)
    function applyFilters() {
        const term = document.getElementById('pos-search-input').value.toLowerCase().trim();
        const items = document.querySelectorAll('.pos-catalog-item');
        let count = 0;
        items.forEach(item => {
            const itemType = item.getAttribute('data-type');
            const name = item.getAttribute('data-name');
            const price = item.getAttribute('data-price');

            const matchType = (currentCatalogFilter === 'all' || itemType === currentCatalogFilter);
            const matchSearch = (!term || name.includes(term) || price.includes(term));

            if (matchType && matchSearch) {
                item.style.display = '';
                count++;
            } else {
                item.style.display = 'none';
            }
        });
        document.getElementById('total-catalogue-count').innerText = count;
    }

    document.getElementById('pos-search-input').addEventListener('input', applyFilters);

    // Ajouter au panier (Produit ou Service)
    function addToCart(type, id, name, price, maxStock, image) {
        if (type === 'product' && maxStock <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Rupture de stock',
                text: 'Ce produit n\'a plus de stock disponible.',
                timer: 2000,
                showConfirmButton: false
            });
            return;
        }

        const cartKey = type + '_' + id;
        const existing = cart.find(item => item.cartKey === cartKey);
        if (existing) {
            if (type === 'product' && existing.qty + 1 > maxStock) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Stock maximum atteint',
                    text: `Vous ne pouvez pas dépasser la quantité disponible en stock (${maxStock}).`,
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }
            existing.qty++;
        } else {
            cart.push({
                cartKey: cartKey,
                type: type,
                id: id,
                name: name,
                price: price,
                qty: 1,
                maxStock: maxStock,
                image: image
            });
        }

        playBeep();
        renderCart();
    }

    // Modifier la quantité
    function updateQty(cartKey, delta) {
        const item = cart.find(i => i.cartKey === cartKey);
        if (!item) return;

        const newQty = item.qty + delta;
        if (newQty <= 0) {
            removeFromCart(cartKey);
            return;
        }
        if (item.type === 'product' && newQty > item.maxStock) {
            Swal.fire({
                icon: 'warning',
                title: 'Stock maximum atteint',
                text: `Stock disponible : ${item.maxStock}`,
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        item.qty = newQty;
        renderCart();
    }

    // Supprimer du panier
    function removeFromCart(cartKey) {
        cart = cart.filter(i => i.cartKey !== cartKey);
        renderCart();
    }

    // Vider le panier
    function clearCart() {
        cart = [];
        renderCart();
    }

    // Affichage du panier
    function renderCart() {
        const container = document.getElementById('cart-items-list');
        const clearBtn = document.getElementById('btn-clear-cart');
        const validateBtn = document.getElementById('btn-validate-sale');

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted" id="empty-cart-msg">
                    <i class="fa-solid fa-cart-arrow-down fs-2 mb-2 text-secondary opacity-50"></i>
                    <p class="small mb-0">Le panier est vide. Tapez sur un élément pour l'ajouter.</p>
                </div>
            `;
            clearBtn.style.display = 'none';
            validateBtn.disabled = true;
            document.getElementById('cart-total-qty').innerText = 0;
            document.getElementById('cart-total-amount').innerText = '0 FCFA';
            calculateChange();
            return;
        }

        clearBtn.style.display = 'block';
        validateBtn.disabled = false;

        let totalQty = 0;
        let totalAmount = 0;
        let html = '';

        cart.forEach(item => {
            const itemTotal = item.price * item.qty;
            totalQty += item.qty;
            totalAmount += itemTotal;

            const typeBadge = item.type === 'service' 
                ? '<span class="badge bg-primary-subtle text-primary" style="font-size:0.65rem;">Service</span>' 
                : '<span class="badge bg-secondary-subtle text-secondary" style="font-size:0.65rem;">Produit</span>';

            html += `
                <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded-3 border bg-white shadow-sm">
                    <div class="d-flex align-items-center gap-2" style="max-width: 55%;">
                        <img src="${item.image}" class="rounded-3" style="width: 40px; height: 40px; object-fit: cover;" alt="">
                        <div class="text-truncate">
                            ${typeBadge}
                            <h6 class="mb-0 text-truncate fw-bold small" title="${item.name}">${item.name}</h6>
                            <small class="text-primary fw-bold">${formatMoney(item.price)} FCFA</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center bg-light rounded-pill border p-1">
                            <button type="button" class="btn btn-sm btn-light qty-btn border-0 text-muted" onclick="updateQty('${item.cartKey}', -1)">-</button>
                            <span class="px-2 fw-bold small">${item.qty}</span>
                            <button type="button" class="btn btn-sm btn-light qty-btn border-0 text-primary" onclick="updateQty('${item.cartKey}', 1)">+</button>
                        </div>
                        <strong class="small text-dark text-nowrap">${formatMoney(itemTotal)} F</strong>
                        <button type="button" class="btn btn-sm text-danger p-0 ms-1" onclick="removeFromCart('${item.cartKey}')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        document.getElementById('cart-total-qty').innerText = totalQty;
        document.getElementById('cart-total-amount').innerText = formatMoney(totalAmount) + ' FCFA';
        calculateChange();
    }

    // Choisir le mode de paiement
    function setPaymentMethod(el, method) {
        document.querySelectorAll('.payment-method-pill').forEach(pill => pill.classList.remove('active'));
        el.classList.add('active');
        selectedPaymentMethod = method;
    }

    // Calcul de la monnaie
    function calculateChange() {
        const total = getCartTotal();
        const paidInput = document.getElementById('pos-amount-paid');
        const paidVal = parseFloat(paidInput.value) || 0;
        const changeBox = document.getElementById('change-display-box');
        const changeVal = document.getElementById('change-amount-val');

        if (paidVal <= 0 || total <= 0) {
            changeBox.classList.add('d-none');
            return;
        }

        changeBox.classList.remove('d-none');
        if (paidVal >= total) {
            const change = paidVal - total;
            changeBox.className = 'p-2 rounded-3 text-center fw-bold bg-success-subtle text-success';
            document.getElementById('change-label').innerText = 'Monnaie à rendre :';
            changeVal.innerText = formatMoney(change) + ' FCFA';
        } else {
            const missing = total - paidVal;
            changeBox.className = 'p-2 rounded-3 text-center fw-bold bg-danger-subtle text-danger';
            document.getElementById('change-label').innerText = 'Reste à payer :';
            changeVal.innerText = formatMoney(missing) + ' FCFA';
        }
    }

    function setExactAmount() {
        const total = getCartTotal();
        document.getElementById('pos-amount-paid').value = total;
        calculateChange();
    }

    function addCash(amount) {
        const input = document.getElementById('pos-amount-paid');
        const current = parseFloat(input.value) || 0;
        input.value = current + amount;
        calculateChange();
    }

    function getCartTotal() {
        return cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
    }

    function formatMoney(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    // Enregistrer un nouveau client rapide (Modal mobile-like)
    function saveQuickCustomer() {
        const fullName = document.getElementById('quick-cust-fullname').value.trim();
        const phone = document.getElementById('quick-cust-phone').value.trim();

        if (!fullName || !phone) {
            Swal.fire({
                icon: 'error',
                title: 'Champs obligatoires',
                text: 'Veuillez renseigner le nom complet et le téléphone du client.',
            });
            return;
        }

        const nameParts = fullName.split(' ');
        const firstName = nameParts[0];
        const lastName = nameParts.slice(1).join(' ') || 'Client';

        $.ajax({
            url: '{{ route("backend.customers.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                first_name: firstName,
                last_name: lastName,
                mobile: phone,
                email: 'client_' + Date.now() + '@salon.local',
                password: 'password123',
            },
            success: function(response) {
                const customerSelect = document.getElementById('pos-customer-select');
                const newOption = document.createElement('option');
                const custId = response.data ? response.data.id : (response.id || Date.now());
                newOption.value = custId;
                newOption.text = fullName + ' (' + phone + ')';
                newOption.selected = true;
                customerSelect.appendChild(newOption);

                const modal = bootstrap.Modal.getInstance(document.getElementById('newCustomerModal'));
                if (modal) modal.hide();

                document.getElementById('quick-cust-fullname').value = '';
                document.getElementById('quick-cust-phone').value = '';

                Swal.fire({
                    icon: 'success',
                    title: 'Client Ajouté !',
                    text: `${fullName} a été sélectionné pour cette vente.`,
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            error: function() {
                // Fallback option directe
                const customerSelect = document.getElementById('pos-customer-select');
                const newOption = document.createElement('option');
                newOption.value = 'new_' + Date.now();
                newOption.text = fullName + ' (' + phone + ')';
                newOption.setAttribute('data-new-name', fullName);
                newOption.setAttribute('data-new-phone', phone);
                newOption.selected = true;
                customerSelect.appendChild(newOption);

                const modal = bootstrap.Modal.getInstance(document.getElementById('newCustomerModal'));
                if (modal) modal.hide();

                document.getElementById('quick-cust-fullname').value = '';
                document.getElementById('quick-cust-phone').value = '';
            }
        });
    }

    // Soumettre la vente
    function submitPosSale() {
        if (cart.length === 0) return;

        const total = getCartTotal();
        const paid = parseFloat(document.getElementById('pos-amount-paid').value) || total;
        const customerSelect = document.getElementById('pos-customer-select');
        const customerId = customerSelect.value;
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];

        const payload = {
            _token: '{{ csrf_token() }}',
            total_amount: total,
            amount_paid: paid,
            payment_method: selectedPaymentMethod,
            customer_id: customerId && !customerId.startsWith('new_') ? customerId : null,
            new_customer_name: selectedOption ? selectedOption.getAttribute('data-new-name') : null,
            new_customer_phone: selectedOption ? selectedOption.getAttribute('data-new-phone') : null,
            items: cart.map(i => ({
                type: i.type,
                product_id: i.type === 'product' ? i.id : null,
                service_id: i.type === 'service' ? i.id : null,
                name: i.name,
                price: i.price,
                quantity: i.qty
            }))
        };

        const validateBtn = document.getElementById('btn-validate-sale');
        validateBtn.disabled = true;
        validateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Validation en cours...';

        $.ajax({
            url: '{{ route("backend.orders.pos_store") }}',
            type: 'POST',
            data: payload,
            success: function(response) {
                if (response.status) {
                    document.getElementById('success-order-code').innerText = '#' + (response.data.order_code || '000000');
                    document.getElementById('success-total-paid').innerText = formatMoney(response.data.total_amount) + ' FCFA';
                    document.getElementById('success-change').innerText = formatMoney(response.data.change || 0) + ' FCFA';
                    document.getElementById('btn-print-invoice').href = response.data.invoice_url || '#';

                    const successModal = new bootstrap.Modal(document.getElementById('saleSuccessModal'));
                    successModal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: response.message || 'Une erreur est survenue lors de la vente.',
                    });
                    validateBtn.disabled = false;
                    validateBtn.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> Valider la Vente';
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Erreur lors de la validation.';
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur de stock ou saisie',
                    text: msg,
                });
                validateBtn.disabled = false;
                validateBtn.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> Valider la Vente';
            }
        });
    }

    function resetPos() {
        cart = [];
        renderCart();
        document.getElementById('pos-amount-paid').value = '';
        document.getElementById('pos-customer-select').value = '';
        calculateChange();
        window.location.reload();
    }
</script>
@endpush
