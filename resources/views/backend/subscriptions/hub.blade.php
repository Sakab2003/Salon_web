@extends('backend.layouts.app')

@section('title', 'Gestion des Abonnements - SALON')

@push('after-styles')
    <!-- Tailwind CSS for faithful replica of subscription hub -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        indigo: {
                            50: '#fcf6fd',
                            100: '#f7ebfa',
                            200: '#efd5f5',
                            300: '#e3b3ed',
                            400: '#d086e0',
                            500: '#b452ca',
                            600: '#752484', // Violet SALON officiel
                            700: '#631c71',
                            800: '#51165d',
                            900: '#3e0e47',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .sub-hub-root {
            font-family: inherit;
        }
        .sub-hub-root input:focus, .sub-hub-root select:focus, .sub-hub-root textarea:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(117, 36, 132, 0.25);
        }
    </style>
@endpush

@section('content')
<div class="sub-hub-root space-y-8 pb-12">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-6">
        <div>
            <h1 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tight">Gestion des Abonnements</h1>
            <p class="text-slate-500 mt-2 text-base lg:text-lg font-medium">Contrôlez l'accès et les périodes de validité du réseau SALON.</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="refreshAll()"
                class="bg-white border border-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold hover:bg-slate-50 transition-all flex items-center shadow-sm">
                <i class="fas fa-sync-alt mr-2 text-indigo-500"></i>
                Actualiser
            </button>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Global -->
        <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-xl shadow-indigo-100 relative overflow-hidden group">
            <div class="absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition-transform">
                <i class="fas fa-layer-group text-8xl"></i>
            </div>
            <p class="text-indigo-100 text-xs font-black uppercase tracking-widest">Total Global</p>
            <h2 id="total-subscriptions" class="text-4xl font-black mt-2">-</h2>
            <div class="mt-4 flex items-center text-xs text-indigo-200 font-bold">
                <i class="fas fa-chart-line mr-1.5"></i>
                Base installée complète
            </div>
        </div>

        <!-- Actifs -->
        <div class="bg-white border border-green-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-600 bg-green-50 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Actifs</span>
                <i class="fas fa-check-circle text-green-500"></i>
            </div>
            <h2 id="active-subscriptions" class="text-3xl font-black text-slate-800">-</h2>
            <p class="text-slate-400 text-xs font-medium mt-1">Abonnements en cours</p>
        </div>

        <!-- Essai -->
        <div class="bg-white border border-amber-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Essai</span>
                <i class="fas fa-hourglass-half text-amber-500"></i>
            </div>
            <h2 id="trial-subscriptions" class="text-3xl font-black text-slate-800">-</h2>
            <p class="text-slate-400 text-xs font-medium mt-1">Gratuité temporaire</p>
        </div>

        <!-- Expirés -->
        <div class="bg-white border border-red-100 rounded-2xl p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <span class="text-red-600 bg-red-50 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Expirés</span>
                <i class="fas fa-times-circle text-red-500"></i>
            </div>
            <h2 id="expired-subscriptions" class="text-3xl font-black text-slate-800">-</h2>
            <p class="text-slate-400 text-xs font-medium mt-1">Nécessitent action</p>
        </div>
    </div>

    <!-- Revenue Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 text-white shadow-xl shadow-slate-200">
            <div class="flex items-center space-x-4">
                <div class="h-14 w-14 bg-white/10 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-hand-holding-usd text-2xl text-emerald-400"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Revenus Cumulés</p>
                    <h3 id="total-revenue" class="text-2xl font-black text-white mt-1">-</h3>
                </div>
            </div>
        </div>
        <div class="bg-white border border-indigo-50 rounded-2xl p-6 shadow-sm flex items-center space-x-4">
            <div class="h-14 w-14 bg-indigo-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-graduate text-2xl text-indigo-600"></i>
            </div>
            <div>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">ARPU (Revenu Moyen)</p>
                <h3 id="average-revenue" class="text-2xl font-black text-slate-800 mt-1">-</h3>
            </div>
        </div>
    </div>

    <!-- Price Configuration (Admin) -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-8 py-5 bg-gradient-to-r from-indigo-50 to-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h3 class="font-black text-slate-800 flex items-center text-lg">
                <i class="fas fa-tags mr-3 text-indigo-600"></i>
                Tarifs des abonnements (Configurés par l'administrateur)
            </h3>
            <span class="text-xs font-bold text-indigo-700 bg-indigo-100/60 px-3 py-1 rounded-full w-fit">
                S'applique directement à l'application mobile
            </span>
        </div>
        <form action="{{ route('backend.subscriptions.update-prices') }}" method="POST" class="p-8">
            @csrf
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold flex items-center">
                    <i class="fas fa-check-circle mr-2 text-emerald-600 text-base"></i>
                    {{ session('success') }}
                </div>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @php
                    $mPlan = ($plans ?? collect())->firstWhere('identifier', 'monthly') ?? ($plans ?? collect())->first();
                    $yPlan = ($plans ?? collect())->firstWhere('identifier', 'yearly') ?? ($plans ?? collect())->last();
                @endphp
                @if($mPlan)
                <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-black text-slate-800">{{ $mPlan->name }}</label>
                        <span class="text-[11px] font-bold text-slate-500 uppercase">{{ $mPlan->duration }} jours</span>
                    </div>
                    <div class="relative">
                        <input type="number" name="prices[{{ $mPlan->id }}]" id="admin-monthly-price"
                            value="{{ $mPlan->amount }}" min="1" required
                            class="w-full pl-4 pr-16 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-black text-slate-800 text-lg">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">FCFA</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">Prix payé par l'utilisateur pour 30 jours.</p>
                </div>
                @endif

                @if($yPlan)
                <div class="p-5 rounded-2xl border-2 border-indigo-200 bg-indigo-50/30 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-black text-indigo-900">{{ $yPlan->name }}</label>
                        <span class="text-[11px] font-bold text-indigo-600 uppercase">{{ $yPlan->duration }} jours</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="relative">
                            <input type="number" name="prices[{{ $yPlan->id }}]" id="admin-yearly-price"
                                value="{{ $yPlan->amount }}" min="1" required
                                class="w-full pl-4 pr-16 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-black text-slate-800 text-lg">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">FCFA</span>
                        </div>
                        <div class="relative">
                            <input type="number" step="0.1" name="discounts[{{ $yPlan->id }}]" id="admin-yearly-discount"
                                value="{{ $yPlan->discount_percentage ?? 20 }}" min="0" max="100"
                                class="w-full pl-4 pr-10 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-black text-emerald-600 text-lg">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">%</span>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">Tarif annuel avec réduction affichée sur mobile.</p>
                </div>
                @endif
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="bg-indigo-600 text-white font-black px-6 py-3 rounded-xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all flex items-center">
                    <i class="fas fa-save mr-2"></i> Enregistrer les tarifs
                </button>
            </div>
        </form>
    </div>

    <!-- Management Tools -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Extend Form -->
        <div class="lg:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-8 py-6 bg-slate-50 border-b border-slate-100">
                <h3 class="font-black text-slate-800 flex items-center">
                    <i class="fas fa-plus-circle mr-3 text-indigo-600"></i>
                    Prolonger un abonnement
                </h3>
            </div>
            <form id="extend-form" onsubmit="event.preventDefault(); extendSubscription();" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Code SALON</label>
                    <div class="relative">
                        <i class="fas fa-fingerprint absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="extend-salon-code" placeholder="EX: SL-VDRVK3" required
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-slate-800 placeholder:text-slate-300 uppercase">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre de jours</label>
                    <div class="relative">
                        <i class="fas fa-calendar-plus absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="number" id="extend-days" min="1" value="30" required
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-slate-800">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Type de forfait</label>
                    <select id="extend-type" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-slate-800">
                        <option value="monthly">Mensuel (Standard)</option>
                        <option value="yearly">Annuel (Premium)</option>
                        <option value="lifetime">À Vie (Exclusif)</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Paiement (FCFA)</label>
                    <div class="relative">
                        <i class="fas fa-money-bill-wave absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="number" id="extend-amount" step="0.01" min="0" placeholder="0.00"
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-slate-800">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Méthode de Paiement</label>
                    <div class="relative group">
                        <i class="fas fa-wallet absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input type="text" id="extend-payment-method" placeholder="Ex: Mobile Money, Cash"
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-slate-800">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Référence Paiement</label>
                    <div class="relative group">
                        <i class="fas fa-receipt absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input type="text" id="extend-payment-ref" placeholder="Ex: TXN123456"
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-bold text-slate-800">
                    </div>
                </div>
                <div class="md:col-span-2 space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Notes administratives</label>
                    <textarea id="extend-notes" rows="2"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-700"
                        placeholder="Informations complémentaires..."></textarea>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" id="btn-submit-extend"
                        class="w-full bg-indigo-600 text-white font-black py-4 rounded-2xl shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all transform hover:-translate-y-0.5 flex items-center justify-center">
                        <i class="fas fa-check mr-2"></i> Valider la Prolongation
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                <h3 class="font-black text-slate-800 mb-6 flex items-center">
                    <i class="fas fa-search-plus mr-3 text-indigo-600"></i>
                    Vérification Rapide
                </h3>
                <form id="search-form" onsubmit="event.preventDefault(); searchSubscription();" class="space-y-4">
                    <input type="text" id="search-salon-code" placeholder="CODE SALON" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 font-black text-center text-slate-800 uppercase tracking-widest">
                    <button type="submit"
                        class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition-colors flex items-center justify-center shadow-md shadow-indigo-100">
                        <i class="fas fa-search mr-2"></i> Rechercher
                    </button>
                </form>
                <div id="search-result" class="mt-8"></div>
            </div>
        </div>
    </div>

    <!-- Subscriptions List -->
    <div class="bg-white rounded-3xl shadow-md border border-slate-100 overflow-hidden">
        <div class="px-8 py-6 bg-white border-b border-slate-50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="font-black text-slate-800 text-lg">Membres du Réseau</h3>
            <div class="flex items-center space-x-2">
                <i class="fas fa-filter text-slate-300 mr-2"></i>
                <select id="status-filter" onchange="loadSubscriptions(this.value)"
                    class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Tous les statuts</option>
                    <option value="trial">Essai</option>
                    <option value="active">Actif</option>
                    <option value="expired">Expiré</option>
                    <option value="cancelled">Annulé</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Code Unique</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisateur / Appareil</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Statut</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Forfait</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Restant</th>
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Payé</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody id="subscriptions-table" class="divide-y divide-slate-50">
                    <tr>
                        <td colspan="7" class="px-8 py-12 text-center">
                            <i class="fas fa-spinner fa-spin text-indigo-500 text-2xl"></i>
                            <p class="mt-4 text-slate-400 font-medium">Chargement des données...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const apiUrl = '/api';
    const csrfToken = '{{ csrf_token() }}';

    document.addEventListener('DOMContentLoaded', function () {
        refreshAll();
    });

    function refreshAll() {
        loadStats();
        loadSubscriptions();
    }

    function loadStats() {
        fetch(`${apiUrl}/subscriptions/stats`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                document.getElementById('total-subscriptions').innerText = stats.total_subscriptions;
                document.getElementById('active-subscriptions').innerText = stats.active_subscriptions;
                document.getElementById('trial-subscriptions').innerText = stats.trial_subscriptions;
                document.getElementById('expired-subscriptions').innerText = stats.expired_subscriptions;
                document.getElementById('total-revenue').innerText = (stats.total_revenue || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' FCFA';
                document.getElementById('average-revenue').innerText = (stats.average_revenue_per_user || 0).toLocaleString('fr-FR') + ' FCFA';
            }
        })
        .catch(err => console.error('Erreur chargement stats:', err));
    }

    function loadSubscriptions(status = '') {
        let url = `${apiUrl}/subscriptions/list`;
        if (status) url += `?status=${status}`;

        fetch(url, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('subscriptions-table');
            tbody.innerHTML = '';

            if (data.success && data.subscriptions.data) {
                const subs = data.subscriptions.data;
                if (subs.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="px-8 py-8 text-center text-slate-400 text-sm font-medium">Aucun abonnement trouvé.</td></tr>`;
                    return;
                }

                subs.forEach(sub => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50/80 transition-colors';

                    const color = getStatusColor(sub.status);
                    const statusBadge = `<span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-${color}-50 text-${color}-600 border border-${color}-100">${sub.status}</span>`;

                    tr.innerHTML = `
                        <td class="px-8 py-4">
                            <span class="font-black text-slate-700 text-sm tracking-wide">#${sub.salon_code || '---'}</span>
                        </td>
                        <td class="px-8 py-4">
                            <div class="font-bold text-slate-800 text-sm">${sub.device_name || 'Appareil Inconnu'}</div>
                            <div class="text-[10px] text-slate-400">${sub.last_activity ? 'Activité: ' + sub.last_activity : ''}</div>
                        </td>
                        <td class="px-8 py-4">${statusBadge}</td>
                        <td class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">${sub.subscription_type || 'N/A'}</td>
                        <td class="px-8 py-4">
                            <span class="text-sm font-black ${sub.remaining_days < 5 ? 'text-red-500' : 'text-slate-800'}">
                                ${sub.remaining_days >= 0 ? sub.remaining_days + ' j' : 'Illimité'}
                            </span>
                        </td>
                        <td class="px-8 py-4 text-sm font-black text-slate-800">${(sub.amount_paid || 0).toLocaleString()} F</td>
                        <td class="px-8 py-4 text-right">
                            <div class="flex justify-end space-x-2">
                                <button onclick="cancelSubscription('${sub.salon_code}')" title="Annuler l'abonnement" class="p-2 text-slate-300 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(err => {
            console.error('Erreur chargement abonnements:', err);
            document.getElementById('subscriptions-table').innerHTML = `<tr><td colspan="7" class="px-8 py-8 text-center text-red-500 text-sm">Erreur de chargement.</td></tr>`;
        });
    }

    function getStatusColor(status) {
        switch (status) {
            case 'active': return 'emerald';
            case 'trial': return 'amber';
            case 'expired': return 'red';
            case 'cancelled': return 'slate';
            default: return 'slate';
        }
    }

    function extendSubscription() {
        const codeInput = document.getElementById('extend-salon-code');
        const daysInput = document.getElementById('extend-days');
        const typeInput = document.getElementById('extend-type');
        const amountInput = document.getElementById('extend-amount');
        const methodInput = document.getElementById('extend-payment-method');
        const refInput = document.getElementById('extend-payment-ref');
        const notesInput = document.getElementById('extend-notes');

        const data = {
            salon_code: codeInput.value.toUpperCase().trim(),
            days: parseInt(daysInput.value) || 30,
            subscription_type: typeInput.value,
            amount_paid: parseFloat(amountInput.value) || 0,
            payment_method: methodInput.value,
            payment_reference: refInput.value,
            notes: notesInput.value
        };

        const btn = document.getElementById('btn-submit-extend');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Traitement...';

        fetch(`${apiUrl}/subscriptions/extend-by-code`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-2"></i> Valider la Prolongation';

            if (result.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Abonnement ${result.subscription.salon_code} prolongé (+${data.days} j)!`,
                    showConfirmButton: false,
                    timer: 3500,
                    timerProgressBar: true
                });
                document.getElementById('extend-form').reset();
                daysInput.value = 30;
                refreshAll();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: result.message || 'Impossible de prolonger l\'abonnement.'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-2"></i> Valider la Prolongation';
            Swal.fire({
                icon: 'error',
                title: 'Erreur réseau',
                text: err.message
            });
        });
    }

    function searchSubscription() {
        const codeInput = document.getElementById('search-salon-code');
        const salonCode = codeInput.value.toUpperCase().trim();
        const resultDiv = document.getElementById('search-result');
        resultDiv.innerHTML = '<div class="text-center py-4"><i class="fas fa-circle-notch fa-spin text-indigo-500 text-xl"></i></div>';

        fetch(`${apiUrl}/subscriptions/check`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ salon_code: salonCode })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.subscription) {
                const sub = data.subscription;
                const statusColor = getStatusColor(sub.status);
                resultDiv.innerHTML = `
                    <div class="p-5 bg-indigo-50 rounded-2xl border border-indigo-100 space-y-3">
                        <div class="flex items-center justify-between border-b border-indigo-100 pb-2 mb-2">
                            <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Résultat trouvé</span>
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[9px] font-bold text-indigo-400 uppercase">Code</p>
                                <p class="text-sm font-black text-indigo-900">#${sub.salon_code}</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-bold text-indigo-400 uppercase">Statut</p>
                                <span class="text-[10px] font-bold bg-white text-indigo-700 px-2 py-0.5 rounded-md border border-indigo-200 uppercase">${sub.status}</span>
                            </div>
                            <div class="col-span-2">
                                <p class="text-[9px] font-bold text-indigo-400 uppercase">Temps Restant</p>
                                <p class="text-xl font-black text-indigo-900">${sub.remaining_days} Jours</p>
                                <p class="text-[10px] text-slate-500 mt-1">${sub.subscription_end_date ? 'Fin : ' + sub.subscription_end_date : ''}</p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `<div class="p-5 bg-red-50 rounded-2xl border border-red-100 text-red-600 text-xs font-bold text-center">${data.message || 'Aucun abonnement trouvé pour ce code.'}</div>`;
            }
        })
        .catch(err => {
            resultDiv.innerHTML = `<div class="p-5 bg-red-50 rounded-2xl border border-red-100 text-red-600 text-xs font-bold text-center">Erreur lors de la recherche.</div>`;
        });
    }

    function cancelSubscription(salonCode) {
        Swal.fire({
            title: 'Confirmer l\'annulation',
            text: `Voulez-vous vraiment annuler l'abonnement ${salonCode} ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Oui, annuler',
            cancelButtonText: 'Non, garder'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`${apiUrl}/subscriptions/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ salon_code: salonCode, reason: 'Annulation administrative' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Abonnement annulé !',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        refreshAll();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: data.message
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: err.message });
                });
            }
        });
    }

    // Synchronisation automatique des prix dans le formulaire de prolongation
    const currentAdminPrices = {
        monthly: {{ ($mPlan->amount ?? 1) }},
        yearly: {{ ($yPlan->amount ?? 10) }},
        lifetime: 0
    };

    document.getElementById("extend-type")?.addEventListener("change", function() {
        const type = this.value;
        const daysInput = document.getElementById("extend-days");
        const amountInput = document.getElementById("extend-amount");
        if (!daysInput || !amountInput) return;

        if (type === "monthly") {
            daysInput.value = 30;
            amountInput.value = currentAdminPrices.monthly;
        } else if (type === "yearly") {
            daysInput.value = 365;
            amountInput.value = currentAdminPrices.yearly;
        } else if (type === "lifetime") {
            daysInput.value = 3650;
            amountInput.value = 0;
        }
    });

    // Initialiser au chargement
    document.addEventListener("DOMContentLoaded", function() {
        const typeSelect = document.getElementById("extend-type");
        if (typeSelect) {
            typeSelect.dispatchEvent(new Event("change"));
        }
    });
</script>
@endpush
