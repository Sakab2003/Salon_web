<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\PulseKangoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionTransactions;

/**
 * Contrôleur unifié de paiement Mobile Money
 * Gère tous les moyens de paiement (Orange, Moov, Telecel, Yennegapay, etc.)
 * via le driver configuré pour chaque gateway en base de données.
 *
 * Flux de paiement mobile money :
 * - Orange Money  : L'utilisateur compose *144*4*6# → reçoit OTP → saisit dans l'app
 * - Moov Money    : USSD Push automatique sur le téléphone (pas d'OTP côté app)
 * - Telecel Money : USSD Push automatique (pas d'OTP côté app)
 */
class PaymentGatewayController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/payment-gateways (public)
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
                // Infos pour le flux UX côté mobile :
                'requires_otp'    => $this->gatewayRequiresOtp($gw->code),
                'otp_instruction' => $this->getOtpInstruction($gw->code),
                'ussd_push'       => $this->isUssdPush($gw->code),
            ];
        });

        return response()->json([
            'status'  => true,
            'data'    => $gateways,
            'message' => 'Passerelles de paiement disponibles',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/v1/plans (authentifié)
    // Retourne les plans actifs avec leurs prix depuis la base de données
    // ─────────────────────────────────────────────────────────────────────────

    public function plans()
    {
        $plans = Plan::where('status', 1)->orderBy('amount')->get()->map(function ($plan) {
            return [
                'id'         => $plan->id,
                'name'       => $plan->name,
                'identifier' => $plan->identifier ?? strtolower($plan->type ?? 'monthly'),
                'amount'     => $plan->amount,
                'duration'   => $plan->duration ?? 30,
                'type'       => $plan->type ?? 'Monthly',
            ];
        });

        return response()->json([
            'status'  => true,
            'data'    => $plans,
            'message' => 'Plans d\'abonnement',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/v1/subscribe (authentifié)
    // Initie un paiement pour un plan donné via la passerelle choisie
    //
    // Body: {
    //   plan_id:      int     — ID du plan
    //   gateway_code: string  — Code du gateway (ex: orange_money_bf)
    //   phone:        string  — Numéro du payeur (+226XXXXXXXX)
    //   otp:          string? — OTP (requis pour Orange Money uniquement)
    // }
    // ─────────────────────────────────────────────────────────────────────────

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id'      => ['required', 'integer'],
            'gateway_code' => ['required', 'string'],
            'phone'        => ['required', 'string', 'min:8'],
            'otp'          => ['nullable', 'string'],
        ]);

        try {
            $user    = $request->user();
            $plan    = Plan::find($request->plan_id);

            if (! $plan) {
                return response()->json(['status' => false, 'message' => 'Plan introuvable.'], 404);
            }

            $gateway = PaymentGateway::where('code', $request->gateway_code)
                ->where('is_active', true)
                ->first();

            if (! $gateway) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Passerelle de paiement introuvable ou inactive.',
                ], 404);
            }

            // Vérification OTP pour Orange Money
            if ($this->gatewayRequiresOtp($gateway->code) && empty($request->otp)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Veuillez générer et saisir votre OTP Orange Money en composant *144*4*6# sur votre téléphone.',
                    'requires_otp' => true,
                ], 422);
            }

            $reference = 'salon_' . $user->id . '_' . time();
            $phone     = $this->normalizePhone($request->phone);

            // ─ Router vers le bon driver ──────────────────────────────────────
            $result = match ($gateway->driver) {
                'pulse_kango' => $this->payWithPulseKango(
                    $gateway, $plan, $user, $phone, $reference, $request->otp
                ),
                default => [
                    'success' => false,
                    'message' => "Driver '{$gateway->driver}' non implémenté. Contactez l'administrateur.",
                ],
            };

            if (! ($result['success'] ?? false)) {
                return response()->json([
                    'status'  => false,
                    'message' => $result['message'] ?? 'Erreur lors de l\'initiation du paiement.',
                    'detail'  => $result['raw'] ?? null,
                ], 400);
            }

            // ─ Créer ou mettre à jour l'abonnement ───────────────────────────
            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id, 'status' => 'pending'],
                [
                    'plan_id'    => $plan->id,
                    'start_date' => now(),
                    'end_date'   => now()->addDays($plan->duration ?? 30),
                    'status'     => 'pending',
                    'amount'     => $plan->amount,
                    'name'       => $plan->name ?? 'Abonnement',
                    'identifier' => $plan->identifier ?? $reference,
                    'type'       => $plan->type ?? 'Monthly',
                    'duration'   => $plan->duration ?? 30,
                ]
            );

            SubscriptionTransactions::create([
                'subscriptions_id' => $subscription->id,
                'user_id'          => $user->id,
                'amount'           => $plan->amount,
                'payment_status'   => 'pending',
                'payment_type'     => $gateway->code,
                'transaction_id'   => $result['transaction_id'] ?? $reference,
            ]);

            Log::info('[Subscribe] Paiement initié avec succès', [
                'user_id'   => $user->id,
                'plan'      => $plan->name,
                'gateway'   => $gateway->code,
                'reference' => $reference,
            ]);

            return response()->json([
                'status'          => true,
                'message'         => $this->getSuccessMessage($gateway->code),
                'payment_url'     => $result['payment_url'] ?? null,
                'transaction_id'  => $result['transaction_id'] ?? null,
                'reference'       => $reference,
                'subscription_id' => $subscription->id,
                'ussd_push'       => $this->isUssdPush($gateway->code),
            ]);

        } catch (\Exception $e) {
            Log::error('[Subscribe] Exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Driver PulseKango
    // ─────────────────────────────────────────────────────────────────────────

    private function payWithPulseKango(PaymentGateway $gateway, Plan $plan, $user, string $phone, string $reference, ?string $otp): array
    {
        $service = new PulseKangoService($gateway->getCredentials());

        $payload = [
            'amount'      => $plan->amount,
            'currency'    => 'XOF',
            'phone'       => $phone,
            'description' => 'Abonnement ' . ($plan->name ?? 'Kuilinga Salon') . ' — Kuilinga',
            'reference'   => $reference,
            'return_url'  => config('services.pulse_kango.return_url', url('/app')),
            'notify_url'  => url('/api/v1/payments/pulse-kango/webhook'),
            'operator'    => $this->getOperatorCode($gateway->code),
        ];

        // OTP pour Orange Money
        if ($otp) {
            $payload['otp'] = $otp;
        }

        return $service->initiatePayment($payload);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers — Logique par opérateur
    // ─────────────────────────────────────────────────────────────────────────

    /** Orange Money nécessite un OTP que l'utilisateur génère en composant *144*4*6# */
    private function gatewayRequiresOtp(string $code): bool
    {
        return str_contains($code, 'orange');
    }

    /** Moov et Telecel envoient un USSD Push direct sur le téléphone */
    private function isUssdPush(string $code): bool
    {
        return str_contains($code, 'moov') || str_contains($code, 'telecel');
    }

    /** Instruction OTP affichée dans l'app mobile */
    private function getOtpInstruction(string $code): ?string
    {
        if (str_contains($code, 'orange')) {
            return "Composez *144*4*6# sur votre téléphone pour générer votre OTP Orange Money, puis saisissez-le ci-dessous.";
        }
        if (str_contains($code, 'moov')) {
            return "Vous recevrez une notification USSD sur votre téléphone Moov pour confirmer le paiement.";
        }
        if (str_contains($code, 'telecel')) {
            return "Vous recevrez une notification USSD sur votre téléphone Telecel pour confirmer le paiement.";
        }
        return null;
    }

    /** Code opérateur pour PulseKango */
    private function getOperatorCode(string $gatewayCode): string
    {
        if (str_contains($gatewayCode, 'orange'))  return 'orange';
        if (str_contains($gatewayCode, 'moov'))    return 'moov';
        if (str_contains($gatewayCode, 'telecel')) return 'telecel';
        return 'mobile_money';
    }

    /** Message de succès adapté à l'opérateur */
    private function getSuccessMessage(string $code): string
    {
        if (str_contains($code, 'orange')) {
            return 'OTP validé. Votre abonnement est en cours de traitement.';
        }
        if (str_contains($code, 'moov') || str_contains($code, 'telecel')) {
            return 'Veuillez confirmer le paiement sur la notification USSD envoyée sur votre téléphone.';
        }
        return 'Paiement initié avec succès.';
    }

    /** Normalise le numéro de téléphone au format international */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);
        if (! str_starts_with($phone, '+')) {
            // Ajouter indicatif Burkina Faso par défaut
            $phone = str_starts_with($phone, '00') ? '+' . substr($phone, 2) : '+226' . ltrim($phone, '0');
        }
        return $phone;
    }
}
