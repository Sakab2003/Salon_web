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
    protected function autoMigrateAndSeedIfNeeded(): void
    {
        try {
            $migrated = false;
            if (!\Illuminate\Support\Facades\Schema::hasTable('payment_gateways')) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $migrated = true;
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('payment_gateways') && PaymentGateway::count() === 0) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'PaymentGatewaySeeder', '--force' => true]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('plans') && Plan::where('status', 1)->count() === 0) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'TestPlanSeeder', '--force' => true]);
            }
            if ($migrated) {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
            }
        } catch (\Throwable $e) {
            Log::warning("autoMigrateAndSeedIfNeeded error: " . $e->getMessage());
        }
    }

    public function index()
    {
        $this->autoMigrateAndSeedIfNeeded();

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('payment_gateways')) {
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

                if ($gateways->isNotEmpty()) {
                    return response()->json([
                        'status'  => true,
                        'data'    => $gateways,
                        'message' => 'Passerelles de paiement disponibles',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("PaymentGateway index fallback: " . $e->getMessage());
        }

        return response()->json([
            'status'  => true,
            'data'    => [
                [
                    'id'              => 1,
                    'name'            => 'Orange Money',
                    'code'            => 'orange_money_bf',
                    'logo_url'        => url('/images/payment/orange_money.png'),
                    'description'     => 'Paiement via Orange Money Burkina Faso (code OTP #144*4*6#)',
                    'phone_prefixes'  => ['04', '05', '06', '07', '54', '55', '56', '57', '64', '65', '66', '67', '74', '75', '76', '77'],
                    'requires_otp'    => true,
                    'otp_instruction' => 'Générez votre code OTP en composant le *144*4*6# sur votre téléphone Orange, puis entrez le code reçu.',
                    'ussd_push'       => false,
                ],
                [
                    'id'              => 2,
                    'name'            => 'Moov Money',
                    'code'            => 'moov_money_bf',
                    'logo_url'        => url('/images/payment/moov_money.png'),
                    'description'     => 'Paiement via Moov Money Burkina Faso (validation push USSD)',
                    'phone_prefixes'  => ['01', '02', '03', '50', '51', '52', '53', '60', '61', '62', '63', '70', '71', '72', '73'],
                    'requires_otp'    => false,
                    'otp_instruction' => 'Un message de validation USSD s\'affichera sur votre écran. Entrez votre code secret pour confirmer le paiement.',
                    'ussd_push'       => true,
                ],
                [
                    'id'              => 3,
                    'name'            => 'Telecel Money',
                    'code'            => 'telecel_money_bf',
                    'logo_url'        => url('/images/payment/telecel_money.png'),
                    'description'     => 'Paiement via Telecel Money Burkina Faso',
                    'phone_prefixes'  => ['58', '68', '69', '78', '79'],
                    'requires_otp'    => false,
                    'otp_instruction' => 'Confirmez le paiement sur votre téléphone Telecel.',
                    'ussd_push'       => true,
                ],
            ],
            'message' => 'Passerelles de paiement disponibles',
        ]);
    }

    public function plans()
    {
        $this->autoMigrateAndSeedIfNeeded();

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('plans')) {
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

                if ($plans->isNotEmpty()) {
                    return response()->json([
                        'status'  => true,
                        'data'    => $plans,
                        'message' => 'Plans d\'abonnement',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Plans fallback: " . $e->getMessage());
        }

        return response()->json([
            'status'  => true,
            'data'    => [
                [
                    'id'                  => 1,
                    'name'                => 'Mensuel (30 jours)',
                    'identifier'          => 'monthly',
                    'amount'              => 100,
                    'duration'            => 30,
                    'type'                => 'Monthly',
                    'discount_percentage' => 0,
                ],
                [
                    'id'                  => 2,
                    'name'                => 'Annuel (365 jours)',
                    'identifier'          => 'yearly',
                    'amount'              => 960,
                    'duration'            => 365,
                    'type'                => 'Yearly',
                    'discount_percentage' => 20,
                ],
            ],
            'message' => 'Plans d\'abonnement',
        ]);
    }

    public function subscriptionStatus(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status'        => false,
                'is_subscribed' => false,
                'message'       => 'Utilisateur non authentifié.',
            ], 401);
        }

        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->first();

        $trialDaysRemaining = method_exists($user, 'mobileTrialDaysRemaining') ? $user->mobileTrialDaysRemaining() : 0;
        $isTrialExpired = $user->mobile_trial_started_at !== null && now()->greaterThan($user->mobile_trial_started_at->copy()->addDays(\App\Models\User::MOBILE_TRIAL_DAYS));

        if ($subscription) {
            $daysRemaining = (int) max(1, ceil(now()->diffInDays($subscription->end_date, false)));
            $planType = (stripos($subscription->name, 'annuel') !== false || stripos($subscription->name, 'an') !== false) ? 'Annuel' : 'Mensuel';
            return response()->json([
                'status'                 => true,
                'is_subscribed'          => true,
                'days_remaining'         => $daysRemaining,
                'plan_name'              => $subscription->name ?? 'Abonnement',
                'plan_type'              => $planType,
                'trial_days_remaining'   => 0,
                'is_trial_expired'       => false,
                'data'                   => [
                    'is_subscribed'        => true,
                    'days_remaining'       => $daysRemaining,
                    'plan_name'            => $subscription->name ?? 'Abonnement',
                    'plan_type'            => $planType,
                    'trial_days_remaining' => 0,
                    'is_trial_expired'     => false,
                    'start_date'           => $subscription->start_date,
                    'end_date'             => $subscription->end_date,
                ],
                'message'                => "Abonnement {$planType} actif : {$subscription->name} ({$daysRemaining} jours restants)",
            ]);
        }

        return response()->json([
            'status'                 => true,
            'is_subscribed'          => false,
            'days_remaining'         => 0,
            'plan_name'              => '',
            'plan_type'              => '',
            'trial_days_remaining'   => $trialDaysRemaining,
            'is_trial_expired'       => $isTrialExpired,
            'data'                   => [
                'is_subscribed'        => false,
                'days_remaining'       => 0,
                'plan_name'            => '',
                'plan_type'            => '',
                'trial_days_remaining' => $trialDaysRemaining,
                'is_trial_expired'     => $isTrialExpired,
            ],
            'message'                => 'Aucun abonnement actif.',
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
            $user = $request->user();

            // Vérifier s'il y a déjà un abonnement actif en cours pour cumuler la durée
            $existingSub = Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('end_date', '>', now())
                ->first();

            $plan = Plan::find($request->plan_id);
            if (! $plan) {
                if ($request->plan_id == 1) {
                    $plan = Plan::firstOrCreate(
                        ['identifier' => 'monthly'],
                        ['name' => 'Mensuel (30 jours)', 'type' => 'Monthly', 'duration' => 30, 'amount' => 100, 'status' => 1]
                    );
                } elseif ($request->plan_id == 2) {
                    $plan = Plan::firstOrCreate(
                        ['identifier' => 'yearly'],
                        ['name' => 'Annuel (365 jours)', 'type' => 'Yearly', 'duration' => 365, 'amount' => 960, 'discount_percentage' => '20.00', 'status' => 1]
                    );
                }
            }

            if (! $plan) {
                return response()->json(['status' => false, 'message' => 'Plan introuvable.'], 404);
            }

            $gateway = PaymentGateway::where('code', $request->gateway_code)
                ->where('is_active', true)
                ->first();

            if (! $gateway && in_array($request->gateway_code, ['orange_money_bf', 'moov_money_bf', 'telecel_money_bf'])) {
                $names = [
                    'orange_money_bf' => 'Orange Money',
                    'moov_money_bf'   => 'Moov Money',
                    'telecel_money_bf' => 'Telecel Money',
                ];
                $cleanCode = str_replace('_bf', '', $request->gateway_code);
                $gateway = PaymentGateway::firstOrCreate(
                    ['code' => $request->gateway_code],
                    [
                        'name'        => $names[$request->gateway_code] ?? 'Mobile Money',
                        'driver'      => 'pulse_kango',
                        'is_active'   => true,
                        'logo_url'    => "/images/payment/{$cleanCode}.png",
                        'sort_order'  => 1,
                    ]
                );
            }

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

            // Si un abonnement actif existe déjà, on prolonge la durée à partir de sa date d'échéance
            $baseEndDate = ($existingSub && \Carbon\Carbon::parse($existingSub->end_date)->isFuture())
                ? \Carbon\Carbon::parse($existingSub->end_date)
                : now();
            $newEndDate = $baseEndDate->copy()->addDays($plan->duration ?? 30);
            $startDate  = ($existingSub && \Carbon\Carbon::parse($existingSub->end_date)->isFuture())
                ? $existingSub->start_date
                : now();

            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id'    => $plan->id,
                    'start_date' => $startDate,
                    'end_date'   => $newEndDate,
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