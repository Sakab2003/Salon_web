<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\PulseKangoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionTransactions;

/**
 * Contrôleur unifié de paiement Mobile Money
 * Gère tous les moyens de paiement (Orange, Moov, Telecel, Yennegapay, etc.)
 * via le driver configuré pour chaque gateway.
 */
class PaymentGatewayController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/payment-gateways
    // Liste les passerelles actives pour l'app mobile
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $gateways = PaymentGateway::active()->get()->map(function ($gw) {
            return [
                'id'              => $gw->id,
                'name'            => $gw->name,
                'code'            => $gw->code,
                'logo_url'        => $gw->logo_url ? url($gw->logo_url) : null,
                'description'     => $gw->description,
                'phone_prefixes'  => $gw->getPrefixesArray(),
            ];
        });

        return response()->json([
            'status'  => true,
            'data'    => $gateways,
            'message' => 'Passerelles de paiement disponibles',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/v1/subscribe
    // Initie un paiement pour un plan donné via la passerelle choisie
    // Body: { plan_id, gateway_code, phone }
    // ─────────────────────────────────────────────────────────────────────────

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id'      => ['required', 'integer', 'exists:plans,id'],
            'gateway_code' => ['required', 'string'],
            'phone'        => ['required', 'string'],
        ]);

        try {
            $user    = $request->user();
            $plan    = Plan::findOrFail($request->plan_id);
            $gateway = PaymentGateway::where('code', $request->gateway_code)
                ->where('is_active', true)
                ->first();

            if (! $gateway) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Passerelle de paiement introuvable ou inactive.',
                ], 404);
            }

            $reference = 'salon_' . $user->id . '_' . time();

            // ─ Router vers le bon driver ──────────────────────────────────────
            $result = match ($gateway->driver) {
                'pulse_kango' => $this->payWithPulseKango($gateway, $plan, $user, $request->phone, $reference),
                default       => [
                    'success' => false,
                    'message' => "Le driver '{$gateway->driver}' n'est pas encore implémenté.",
                ],
            };

            if (! $result['success']) {
                return response()->json([
                    'status'  => false,
                    'message' => $result['message'] ?? 'Erreur lors de l\'initiation du paiement.',
                ], 400);
            }

            // ─ Enregistrer la souscription en BDD ────────────────────────────
            $subscription = Subscription::create([
                'plan_id'    => $plan->id,
                'user_id'    => $user->id,
                'start_date' => now(),
                'end_date'   => now()->addDays($plan->duration ?? 30),
                'status'     => config('constant.SUBSCRIPTION_STATUS.PENDING', 'pending'),
                'amount'     => $plan->amount,
                'name'       => $plan->name,
                'identifier' => $plan->identifier ?? $reference,
                'type'       => $plan->type ?? 'Monthly',
                'duration'   => $plan->duration ?? 30,
            ]);

            SubscriptionTransactions::create([
                'subscriptions_id' => $subscription->id,
                'user_id'          => $user->id,
                'amount'           => $plan->amount,
                'payment_status'   => 'pending',
                'payment_type'     => $gateway->code,
                'transaction_id'   => $result['transaction_id'] ?? $reference,
            ]);

            Log::info('[Subscribe] Paiement initié', [
                'user_id'      => $user->id,
                'plan'         => $plan->name,
                'gateway'      => $gateway->code,
                'reference'    => $reference,
                'payment_url'  => $result['payment_url'] ?? null,
            ]);

            return response()->json([
                'status'          => true,
                'message'         => 'Paiement initié. Confirmez sur votre téléphone.',
                'payment_url'     => $result['payment_url'] ?? null,
                'transaction_id'  => $result['transaction_id'] ?? null,
                'reference'       => $reference,
                'subscription_id' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[Subscribe] Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Driver PulseKango
    // ─────────────────────────────────────────────────────────────────────────

    private function payWithPulseKango(PaymentGateway $gateway, Plan $plan, $user, string $phone, string $reference): array
    {
        $service = new PulseKangoService($gateway->getCredentials());

        return $service->initiatePayment([
            'amount'      => $plan->amount,
            'currency'    => 'XOF',
            'phone'       => $phone,
            'description' => 'Abonnement ' . $plan->name . ' — Kuilinga Salon',
            'reference'   => $reference,
            'return_url'  => config('services.pulse_kango.return_url', url('/app/subscriptions')),
            'notify_url'  => url('/api/v1/payments/pulse-kango/webhook'),
        ]);
    }
}
