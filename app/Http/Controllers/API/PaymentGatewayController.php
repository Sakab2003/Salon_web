<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\PulseKangoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionTransactions;

class PaymentGatewayController extends Controller
{
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

    public function plans()
    {
        $plans = Plan::where('status', 1)->orderBy('duration', 'asc')->get()->map(function ($plan) {
            return [
                'id'                  => $plan->id,
                'name'                => $plan->name,
                'identifier'          => $plan->identifier ?? strtolower($plan->type ?? 'monthly'),
                'amount'              => $plan->amount,
                'duration'            => $plan->duration ?? 30,
                'type'                => $plan->type ?? 'Monthly',
                'discount_percentage' => $plan->discount_percentage,
            ];
        });

        return response()->json([
            'status'  => true,
            'data'    => $plans,
            'message' => 'Plans d\'abonnement',
        ]);
    }

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

            if ($this->gatewayRequiresOtp($gateway->code) && empty($request->otp)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Veuillez saisir le code OTP pour continuer le paiement.',
                    'requires_otp' => true,
                ], 422);
            }

            $reference = 'salon_' . $user->id . '_' . time();
            $phone     = $this->normalizePhone($request->phone);

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

            $isPaid = ($result['status'] ?? '') === 'TS';
            $subStatus = $isPaid ? 'active' : 'pending';
            $txStatus  = $isPaid ? 'paid' : 'pending';

            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id'    => $plan->id,
                    'start_date' => now(),
                    'end_date'   => now()->addDays($plan->duration ?? 30),
                    'status'     => $subStatus,
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
                'payment_status'   => $txStatus,
                'payment_type'     => $gateway->code,
                'transaction_id'   => $result['transaction_id'] ?? $reference,
            ]);

            Log::info('[Subscribe] Paiement initié avec succès', [
                'user_id'   => $user->id,
                'plan'      => $plan->name,
                'gateway'   => $gateway->code,
                'reference' => $reference,
            ]);

            $successMsg = $isPaid
                ? 'Paiement effectué avec succès ! Votre abonnement est maintenant actif.'
                : 'Paiement initié. Veuillez confirmer la transaction sur votre téléphone.';

            return response()->json([
                'status'          => true,
                'message'         => $successMsg,
                'payment_url'     => $result['payment_url'] ?? null,
                'transaction_id'  => $result['transaction_id'] ?? null,
                'reference'       => $reference,
                'subscription_id' => $subscription->id,
                'ussd_push'       => $this->isUssdPush($gateway->code),
            ]);

        } catch (\Exception $e) {
            Log::error('[Subscribe] Exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], 500);
        }
    }

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

        if ($payload['operator'] === 'MOOVBF') {
            $externalId = Cache::get('moov_otp_' . $phone);
            if ($externalId) {
                $payload['external_id'] = $externalId;
            }
        }

        if ($otp) {
            $payload['otp'] = $otp;
        }

        $result = $service->initiatePayment($payload);

        if (isset($result['is_otp_step']) && $result['is_otp_step']) {
            if (isset($result['external_id'])) {
                Cache::put('moov_otp_' . $phone, $result['external_id'], now()->addMinutes(5));
            }
            
            return [
                'success' => false,
                'message' => $result['message'] ?? "Un SMS avec l'OTP a été envoyé. Veuillez le saisir pour valider.",
                'raw'     => $result,
            ];
        }

        if (($result['success'] ?? false) && $payload['operator'] === 'MOOVBF') {
            Cache::forget('moov_otp_' . $phone);
        }

        return $result;
    }

    private function gatewayRequiresOtp(string $code): bool
    {
        return str_contains($code, 'orange');
    }

    private function isUssdPush(string $code): bool
    {
        return false;
    }

    private function getOtpInstruction(string $code): ?string
    {
        if (str_contains($code, 'orange')) {
            return "Composez *144*4*6# sur votre téléphone pour générer votre OTP, puis saisissez-le.";
        }
        if (str_contains($code, 'moov')) {
            return "Saisissez votre code OTP Moov.";
        }
        if (str_contains($code, 'telecel')) {
            return "Saisissez votre code OTP Telecel.";
        }
        return "Saisissez votre code OTP.";
    }

    private function getOperatorCode(string $gatewayCode): string
    {
        if (str_contains($gatewayCode, 'orange'))  return 'OMBF';
        if (str_contains($gatewayCode, 'moov'))    return 'MOOVBF';
        if (str_contains($gatewayCode, 'telecel')) return 'TELBF';
        return 'OMBF'; 
    }

    private function getSuccessMessage(string $code): string
    {
        return 'Paiement initié avec succès.';
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '226') && strlen($phone) > 8) {
            $phone = substr($phone, 3);
        }
        return $phone;
    }
}