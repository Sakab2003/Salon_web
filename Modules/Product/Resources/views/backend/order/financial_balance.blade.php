@extends('backend.layouts.app')

@section('title')
    {{ __('Bilan Financier') }}
@endsection

@section('content')
<div class="container-fluid px-0">
    <!-- En-tête -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Bilan Financier & Statistiques</h4>
            <p class="text-muted small mb-0">Vue synthétique des revenus, transactions et performances de vente de produits et services</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('backend.orders.pos') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-cash-register me-1"></i> Nouvelle Vente (POS)
            </a>
            <a href="{{ route('backend.orders.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> Historique des Ventes
            </a>
        </div>
    </div>

    <!-- 4 Cartes Synthétiques -->
    <div class="row g-3 mb-4">
        <!-- Aujourd'hui -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-gradient" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-white-50">AUJOURD'HUI</span>
                    <span class="p-2 rounded-circle bg-white bg-opacity-25"><i class="fa-solid fa-calendar-day"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-white">{{ \Currency::format($dailyTotal) }}</h3>
                <div class="small text-white-50"><i class="fa-solid fa-bag-shopping me-1"></i> {{ $todaySalesCount }} vente(s) effectuée(s)</div>
            </div>
        </div>

        <!-- 7 Derniers Jours -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-gradient" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-white-50">7 DERNIERS JOURS</span>
                    <span class="p-2 rounded-circle bg-white bg-opacity-25"><i class="fa-solid fa-calendar-week"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-white">{{ \Currency::format($weeklyTotal) }}</h3>
                <div class="small text-white-50"><i class="fa-solid fa-chart-line me-1"></i> Performance hebdomadaire</div>
            </div>
        </div>

        <!-- Mois en cours -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-gradient" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-white-50">CE MOIS-CI</span>
                    <span class="p-2 rounded-circle bg-white bg-opacity-25"><i class="fa-solid fa-calendar-days"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-white">{{ \Currency::format($monthlyTotal) }}</h3>
                <div class="small text-white-50"><i class="fa-solid fa-coins me-1"></i> Recettes du mois</div>
            </div>
        </div>

        <!-- Total Global -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-gradient" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-white-50">TOTAL GLOBAL</span>
                    <span class="p-2 rounded-circle bg-white bg-opacity-25"><i class="fa-solid fa-vault"></i></span>
                </div>
                <h3 class="fw-bold mb-1 text-white">{{ \Currency::format($overallTotal) }}</h3>
                <div class="small text-white-50"><i class="fa-solid fa-circle-check me-1"></i> Cumul total encaissé</div>
            </div>
        </div>
    </div>

    <!-- Graphiques et Répartition -->
    <div class="row g-4 mb-4">
        <!-- Évolution des 7 derniers jours -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-chart-column text-primary me-2"></i> Évolution des 7 Derniers Jours</h5>
                        <span class="badge bg-light text-dark px-3 py-2 rounded-pill">Recettes journalières</span>
                    </div>
                    <div id="chart-7-days" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Modes de Paiement -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-primary me-2"></i> Modes de Paiement</h5>
                    
                    @if(count($paymentMethodsRevenue) > 0)
                        <div id="chart-payment-methods" style="height: 220px;"></div>
                        <div class="mt-3">
                            @foreach($paymentMethodsRevenue as $method => $amount)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                    <span class="small fw-bold"><i class="fa-solid fa-circle text-primary fs-6 me-1" style="font-size: 8px !important;"></i> {{ $method }}</span>
                                    <span class="fw-bold text-success small">{{ \Currency::format($amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fa-solid fa-receipt fs-1 mb-2 text-secondary opacity-50"></i>
                            <p class="small mb-0">Aucune transaction enregistrée pour le moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 des Produits les plus vendus -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-fire text-danger me-2"></i> Top des Produits les Plus Vendus</h5>
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">Performances par produit</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="rounded-start">#</th>
                            <th>Produit</th>
                            <th>Prix Unitaire</th>
                            <th>Quantité Vendue</th>
                            <th>Stock Restant</th>
                            <th class="rounded-end">Total Généré</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSellingProducts as $index => $prod)
                            @php
                                $price = (float) ($prod->max_price ?: $prod->min_price);
                                $totalGen = $price * $prod->total_sale_count;
                                $stock = (int) $prod->stock_qty;
                            @endphp
                            <tr>
                                <td><span class="badge bg-light text-dark rounded-circle p-2" style="width: 28px; height: 28px;">{{ $index + 1 }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $prod->feature_image ?: 'https://dummyimage.com/60x60/e2e8f0/64748b.png&text=P' }}" class="rounded-3" style="width: 44px; height: 44px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold">{{ $prod->name }}</div>
                                            <small class="text-muted">{{ $prod->brand->name ?? 'Salon' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold">{{ \Currency::format($price) }}</td>
                                <td>
                                    <span class="badge bg-primary px-3 py-2 rounded-pill fs-6">
                                        <i class="fa-solid fa-cart-shopping me-1"></i> {{ $prod->total_sale_count }}
                                    </span>
                                </td>
                                <td>
                                    @if($stock <= 0)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill">Rupture</span>
                                    @elseif($stock <= 5)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">{{ $stock }} (Faible)</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill">{{ $stock }} en stock</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-success fs-6">{{ \Currency::format($totalGen) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Aucune vente de produit enregistrée pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Données 7 jours
        const chartData7Days = @json($dailyRevenueLast7Days);
        const categories7Days = chartData7Days.map(d => d.date + ' (' + d.day_name + ')');
        const series7Days = chartData7Days.map(d => d.total);

        const options7Days = {
            series: [{
                name: 'Recettes (FCFA)',
                data: series7Days
            }],
            chart: {
                type: 'area',
                height: 300,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            colors: ['#0d6efd'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [20, 100]
                }
            },
            xaxis: {
                categories: categories7Days
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toLocaleString() + ' F';
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString() + ' FCFA';
                    }
                }
            }
        };

        const chart7DaysEl = document.querySelector("#chart-7-days");
        if (chart7DaysEl) {
            new ApexCharts(chart7DaysEl, options7Days).render();
        }

        // Données Modes de paiement
        const paymentData = @json($paymentMethodsRevenue);
        const paymentLabels = Object.keys(paymentData);
        const paymentSeries = Object.values(paymentData);

        if (paymentLabels.length > 0) {
            const optionsPayment = {
                series: paymentSeries,
                labels: paymentLabels,
                chart: {
                    type: 'donut',
                    height: 220
                },
                colors: ['#10b981', '#f97316', '#3b82f6', '#06b6d4', '#8b5cf6'],
                legend: { show: false },
                dataLabels: { enabled: false },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val.toLocaleString() + ' FCFA';
                        }
                    }
                }
            };

            const chartPaymentEl = document.querySelector("#chart-payment-methods");
            if (chartPaymentEl) {
                new ApexCharts(chartPaymentEl, optionsPayment).render();
            }
        }
    });
</script>
@endpush
