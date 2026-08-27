@extends('backend.layouts.app')

@section('title')
    {{ __('Historique des Ventes') }}
@endsection

@push('after-styles')
<!-- DataTables Core and Extensions -->
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
<style>
    .stat-card-gradient {
        background: linear-gradient(135deg, rgba(111, 66, 193, 0.08) 0%, rgba(214, 51, 132, 0.08) 100%);
        border: 1px solid rgba(111, 66, 193, 0.15);
        border-radius: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card-gradient:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.06);
    }
    .stat-icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .ticket-paper {
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
</style>
@endpush

@section('banner-button')
<a href="{{ route('backend.orders.pos') }}" class="btn btn-primary rounded-pill shadow-sm">
    <i class="fa-solid fa-cash-register me-1"></i> Nouvelle Vente (POS)
</a>
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- 4 KPI Cards Alignés sur la Version Mobile -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card-gradient h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Chiffre d'Affaires</span>
                        <h4 class="fw-bolder text-primary mb-0">{{ number_format($totalSalesAmount, 0, ',', ' ') }} FCFA</h4>
                        <small class="text-success"><i class="fa-solid fa-arrow-trend-up me-1"></i>Ventes cumulées</small>
                    </div>
                    <div class="stat-icon-circle bg-primary-subtle text-primary">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card-gradient h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Ventes</span>
                        <h4 class="fw-bolder text-dark mb-0">{{ $totalOrdersCount }}</h4>
                        <small class="text-muted">Commandes encaissées</small>
                    </div>
                    <div class="stat-icon-circle bg-info-subtle text-info">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card-gradient h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Panier Moyen</span>
                        <h4 class="fw-bolder text-dark mb-0">{{ number_format($averageTicket, 0, ',', ' ') }} FCFA</h4>
                        <small class="text-muted">Par transaction</small>
                    </div>
                    <div class="stat-icon-circle bg-success-subtle text-success">
                        <i class="fa-solid fa-basket-shopping"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 stat-card-gradient h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Ventes d'Aujourd'hui</span>
                        <h4 class="fw-bolder text-success mb-0">{{ number_format($todaySalesAmount, 0, ',', ' ') }} FCFA</h4>
                        <small class="text-muted">{{ $todayOrdersCount }} vente(s) aujourd'hui</small>
                    </div>
                    <div class="stat-icon-circle bg-warning-subtle text-warning">
                        <i class="fa-solid fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau Historique des Ventes -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Journal des Ventes & Encaissements</h5>
                    <p class="text-muted small mb-0">Historique complet des ventes de produits et prestations réalisées au salon</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <!-- Filtre Mode de Paiement -->
                    <select name="payment_method_filter" id="payment_method_filter" class="form-select form-select-sm rounded-pill" style="min-width: 160px;">
                        <option value="">Tous les paiements</option>
                        <option value="Paiement Cash">Cash</option>
                        <option value="Orange Money">Orange Money</option>
                        <option value="Moov Money">Moov Money</option>
                        <option value="Wave">Wave</option>
                        <option value="Carte Bancaire">Carte Bancaire</option>
                    </select>

                    <!-- Recherche -->
                    <div class="input-group input-group-sm rounded-pill overflow-hidden border" style="max-width: 250px;">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="sales_search_input" class="form-control bg-light border-0" placeholder="Rechercher...">
                    </div>
                </div>
            </div>

            <table id="sales-datatable" class="table table-hover align-middle border-top">
            </table>
        </div>
    </div>
</div>

<!-- Modal Détail du Ticket de Caisse / Facture -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-receipt text-primary me-2"></i> Reçu de Caisse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="ticket-paper p-4 text-center">
                    <h5 class="fw-bold text-uppercase mb-1" id="receipt-salon-name">{{ auth()->user()->branch->name ?? 'Salon de Coiffure' }}</h5>
                    <p class="text-muted small mb-2" id="receipt-date">-</p>
                    <div class="badge bg-soft-primary px-3 py-1 mb-3" id="receipt-code">#000000</div>

                    <div class="d-flex justify-content-between text-start small mb-3 border-bottom pb-2">
                        <span class="text-muted">Client :</span>
                        <strong id="receipt-customer">-</strong>
                    </div>

                    <table class="table table-sm text-start mb-3">
                        <thead>
                            <tr class="text-muted small">
                                <th>Article</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="receipt-items-tbody">
                        </tbody>
                    </table>

                    <div class="border-top pt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold">Total Payé :</span>
                            <h4 class="fw-bolder text-primary mb-0" id="receipt-total">0 FCFA</h4>
                        </div>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Mode de règlement :</span>
                            <strong id="receipt-method">Cash</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-pill flex-fill" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary rounded-pill flex-fill" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> Imprimer
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<!-- DataTables Core and Extensions -->
<script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const columns = [
            {
                data: 'order_code',
                name: 'order_code',
                title: 'N° Commande',
                orderable: false,
            },
            {
                data: 'customer_name',
                name: 'customer_name',
                title: 'Client',
                orderable: false,
            },
            {
                data: 'placed_on',
                name: 'placed_on',
                title: 'Date & Heure',
                orderable: true,
            },
            {
                data: 'items_detail',
                name: 'items_detail',
                title: 'Articles / Prestations',
                orderable: false,
            },
            {
                data: 'payment_method',
                name: 'payment_method',
                title: 'Paiement',
                orderable: false,
            },
            {
                data: 'total_amount_formatted',
                name: 'total_amount_formatted',
                title: 'Montant Total',
                orderable: false,
            },
            {
                data: 'status',
                name: 'status',
                title: 'Statut',
                orderable: false,
            },
            {
                data: 'action',
                name: 'action',
                title: 'Actions',
                orderable: false,
                searchable: false,
                className: 'text-end',
            }
        ];

        const dataTable = $('#sales-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("backend.orders.index_data") }}',
                data: function(d) {
                    d.filter = {
                        payment_method: $('#payment_method_filter').val(),
                        search: $('#sales_search_input').val(),
                    };
                    d.search = { value: $('#sales_search_input').val() };
                }
            },
            columns: columns,
            order: [[2, 'desc']],
            dom: '<"table-responsive"t><"d-flex flex-wrap justify-content-between align-items-center p-3"<"text-muted small"i><p>>',
            language: {
                emptyTable: "Aucune vente enregistrée pour le moment.",
                info: "Affichage de _START_ à _END_ sur _TOTAL_ ventes",
                infoEmpty: "Affichage de 0 à 0 sur 0 vente",
                infoFiltered: "(filtré de _MAX_ ventes au total)",
                loadingRecords: "Chargement...",
                processing: "Traitement...",
                zeroRecords: "Aucune vente correspondante trouvée",
                paginate: {
                    first: "Premier",
                    last: "Dernier",
                    next: "Suivant",
                    previous: "Précédent"
                }
            }
        });

        $('#payment_method_filter').on('change', function() {
            dataTable.ajax.reload();
        });

        $('#sales_search_input').on('keyup', function() {
            dataTable.ajax.reload();
        });

        // Affichage du ticket de caisse
        $(document).on('click', '.view-receipt-btn', function() {
            const rawData = $(this).attr('data-order-data');
            if (!rawData) return;
            try {
                const data = JSON.parse(rawData);
                $('#receipt-code').text('#' + data.order_code);
                $('#receipt-date').text(data.date);
                $('#receipt-customer').text(data.customer_name + ' (' + data.customer_phone + ')');
                $('#receipt-total').text(data.total);
                $('#receipt-method').text(data.payment_method);

                let tbodyHtml = '';
                (data.items || []).forEach(item => {
                    tbodyHtml += `
                        <tr>
                            <td>${item.name}</td>
                            <td class="text-center font-weight-bold">${item.qty}</td>
                            <td class="text-end font-weight-bold">${item.total}</td>
                        </tr>
                    `;
                });
                $('#receipt-items-tbody').html(tbodyHtml || '<tr><td colspan="3" class="text-center text-muted">Aucun article</td></tr>');

                const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
                modal.show();
            } catch(e) {
                console.error(e);
            }
        });
    });
</script>
@endpush
