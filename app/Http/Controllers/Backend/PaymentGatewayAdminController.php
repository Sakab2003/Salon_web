<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

/**
 * Gestion des passerelles de paiement depuis l'admin web
 * Admin peut : ajouter, modifier, activer/désactiver des moyens de paiement
 */
class PaymentGatewayAdminController extends Controller
{
    public function index()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('payment_gateways')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('payment_gateways') && PaymentGateway::count() === 0) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'PaymentGatewaySeeder', '--force' => true]);
            }
        $hasPlanTable = \Illuminate\Support\Facades\Schema::hasTable('plan') || \Illuminate\Support\Facades\Schema::hasTable('plans');
        if ($hasPlanTable && \Modules\Subscriptions\Models\Plan::where('status', 1)->count() === 0) {
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'TestPlanSeeder', '--force' => true]);
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning("Auto-migrate in PaymentGatewayAdminController: " . $e->getMessage());
    }

    try {
        $gateways = PaymentGateway::orderBy('sort_order')->get();
    } catch (\Throwable $e) {
        $gateways = collect();
    }

    // Récupération des plans avec fallbacks multiples
    $monthlyPlan = null;
    $yearlyPlan = null;

    try {
        $monthlyPlan = \Modules\Subscriptions\Models\Plan::where('identifier', 'monthly')->first()
            ?? \Modules\Subscriptions\Models\Plan::where('type', 'Monthly')->first()
            ?? \Modules\Subscriptions\Models\Plan::where('duration', '<=', 31)->orderBy('duration', 'desc')->first();

        $yearlyPlan = \Modules\Subscriptions\Models\Plan::where('identifier', 'yearly')->first()
            ?? \Modules\Subscriptions\Models\Plan::where('type', 'Yearly')->first()
            ?? \Modules\Subscriptions\Models\Plan::where('duration', '>=', 360)->orderBy('duration', 'desc')->first();

        // Si manquant, auto-création pour garantir l'affichage dans l'interface
        if (!$monthlyPlan) {
            $monthlyPlan = \Modules\Subscriptions\Models\Plan::create([
                'name'       => 'Mensuel (30 jours)',
                'identifier' => 'monthly',
                'type'       => 'Monthly',
                'duration'   => 30,
                'amount'     => 100,
                'status'     => 1,
            ]);
        }
        if (!$yearlyPlan) {
            $yearlyPlan = \Modules\Subscriptions\Models\Plan::create([
                'name'                => 'Annuel (365 jours)',
                'identifier'          => 'yearly',
                'type'                => 'Yearly',
                'duration'            => 365,
                'amount'              => 960,
                'discount_percentage' => 20,
                'status'              => 1,
            ]);
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning("Could not load/create plans: " . $e->getMessage());
    }

    return view('backend.payment_gateways.index', compact('gateways', 'monthlyPlan', 'yearlyPlan'));
    }

    public function create()
    {
        return view('backend.payment_gateways.edit', ['gateway' => new PaymentGateway()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateGateway($request);
        $data['config'] = $this->buildConfig($request);

        PaymentGateway::create($data);

        return redirect()->route('backend.payment-gateways.index')
            ->with('success', 'Passerelle de paiement créée avec succès.');
    }

    public function edit(PaymentGateway $paymentGateway)
    {
        return view('backend.payment_gateways.edit', ['gateway' => $paymentGateway]);
    }

    public function update(Request $request, PaymentGateway $paymentGateway)
    {
        $data = $this->validateGateway($request, $paymentGateway->id);
        $data['config'] = $this->buildConfig($request, $paymentGateway->config);

        $paymentGateway->update($data);

        return redirect()->route('backend.payment-gateways.index')
            ->with('success', 'Passerelle mise à jour avec succès.');
    }

    public function destroy(PaymentGateway $paymentGateway)
    {
        $paymentGateway->delete();
        return redirect()->route('backend.payment-gateways.index')
            ->with('success', 'Passerelle supprimée.');
    }

    public function toggleStatus(PaymentGateway $paymentGateway)
    {
        $paymentGateway->update(['is_active' => ! $paymentGateway->is_active]);
        return response()->json(['status' => true, 'is_active' => $paymentGateway->is_active]);
    }

    public function updatePrices(Request $request)
    {
        $request->validate([
            'prices' => 'required|array',
            'discounts' => 'nullable|array'
        ]);

        foreach ($request->prices as $planId => $amount) {
            $discount = $request->input("discounts.{$planId}");
            \Modules\Subscriptions\Models\Plan::where('id', $planId)
                ->update([
                    'amount' => max(1, (int) $amount),
                    'discount_percentage' => $discount !== null ? max(0, (float) $discount) : null
                ]);
        }

        try {
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Throwable $e) {}

        return redirect()->route('backend.payment-gateways.index')
            ->with('success', '✅ Prix mis à jour avec succès. L\'application mobile utilisera ces nouveaux prix.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function validateGateway(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'code'           => ['required', 'string', 'max:50', 'unique:payment_gateways,code,' . $excludeId],
            'driver'         => ['required', 'string'],
            'description'    => ['nullable', 'string'],
            'logo_url'       => ['nullable', 'string'],
            'phone_prefixes' => ['nullable', 'string'],
            'is_active'      => ['sometimes', 'boolean'],
            'sort_order'     => ['nullable', 'integer'],
        ]);
    }

    private function buildConfig(Request $request, ?array $existingConfig = null): ?array
    {
        $config = $existingConfig ?? [];

        // Champs config dynamiques : api_key, username, secret, base_url, etc.
        $configFields = ['api_key', 'username', 'secret', 'base_url', 'merchant_id', 'site_id'];
        foreach ($configFields as $field) {
            $val = $request->input("config_{$field}");
            if ($val !== null && $val !== '') {
                $config[$field] = $val;
            }
        }

        return empty($config) ? null : $config;
    }
}
