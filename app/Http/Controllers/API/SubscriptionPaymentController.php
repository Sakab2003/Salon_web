<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CinetPaySubscriptionGateway;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Subscriptions\Models\Subscription;
use RuntimeException;

class SubscriptionPaymentController extends Controller
{
    public function __construct(private CinetPaySubscriptionGateway $gateway)
    {
    }

    public function subscribe(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('manager'), 403, 'Seul le manager du salon peut souscrire un abonnement.');

        $amount = (int) config('services.cinetpay.subscription_amount', 0);
        abort_if($amount < 500, 422, 'Le tarif de l’abonnement n’est pas encore configuré.');

        $duration = max(1, (int) config('services.cinetpay.subscription_duration_days', 30));
        $transactionId = 'SALON-'.now()->format('YmdHis').'-'.$user->id.'-'.Str::upper(Str::random(6));

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'start_date' => now(),
            'end_date' => now()->addDays($duration),
            'status' => config('constant.SUBSCRIPTION_STATUS.PENDING'),
            'amount' => $amount,
            'name' => 'Abonnement mobile '.$duration.' jours',
            'identifier' => 'mobile-'.$duration.'-jours',
            'type' => 'days',
            'duration' => $duration,
            'plan_type' => 'Unlimited',
            'payment_transaction_id' => $transactionId,
            'payment_provider' => 'cinetpay',
        ]);

        try {
            $payment = $this->gateway->initialize($user, $transactionId, $amount);
        } catch (RuntimeException $exception) {
            $subscription->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);
            return response()->json(['status' => false, 'message' => $exception->getMessage()], 422);
        }

        $subscription->update([
            'payment_url' => data_get($payment, 'data.payment_url'),
            'payment_payload' => json_encode($payment),
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'transaction_id' => $transactionId,
                'payment_url' => $subscription->payment_url,
                'amount' => $amount,
                'currency' => config('services.cinetpay.currency', 'XOF'),
            ],
            'message' => 'Paiement initialisé. Finalisez-le dans la page sécurisée.',
        ], 201);
    }

    public function status(Request $request)
    {
        $summary = $request->user()->mobileAccessSummary();
        $subscription = $summary['subscription'];

        return response()->json([
            'status' => true,
            'data' => [
                'active' => $summary['active'],
                'is_trial' => $summary['is_trial'],
                'trial_days_remaining' => $summary['trial_days_remaining'],
                'owner_id' => $summary['owner_id'],
                'subscription' => $subscription ? [
                    'status' => $subscription->status,
                    'starts_at' => $subscription->start_date,
                    'ends_at' => $subscription->end_date,
                ] : null,
            ],
        ]);
    }

    /**
     * CinetPay calls this endpoint more than once. The provider is always
     * queried again; posted values alone can never activate an account.
     */
    public function webhook(Request $request)
    {
        $transactionId = (string) $request->input('cpm_trans_id', $request->input('transaction_id', ''));
        if ($transactionId === '') {
            return response()->json(['status' => false, 'message' => 'Transaction manquante.'], 422);
        }

        $subscription = Subscription::where('payment_transaction_id', $transactionId)->first();
        if (! $subscription) {
            return response()->json(['status' => false, 'message' => 'Transaction inconnue.'], 404);
        }

        try {
            $verification = $this->gateway->verify($transactionId);
        } catch (RuntimeException $exception) {
            report($exception);
            return response()->json(['status' => false, 'message' => 'Vérification différée.'], 503);
        }

        $paymentStatus = strtoupper((string) data_get($verification, 'data.status', data_get($verification, 'message', '')));
        $amount = (int) data_get($verification, 'data.amount', 0);
        $siteId = (string) data_get($verification, 'data.site_id', '');
        $validSite = $siteId === '' || $siteId === (string) config('services.cinetpay.site_id');

        $subscription->update(['payment_payload' => json_encode($verification)]);

        if (in_array($paymentStatus, ['ACCEPTED', 'SUCCESS'], true) && $amount === (int) $subscription->amount && $validSite) {
            $startsAt = now();
            $active = Subscription::where('user_id', $subscription->user_id)
                ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                ->where('end_date', '>', now())
                ->latest('end_date')
                ->first();
            if ($active) {
                $startsAt = Carbon::parse($active->end_date);
                $active->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);
            }

            $subscription->update([
                'status' => config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
                'start_date' => $startsAt,
                'end_date' => $startsAt->copy()->addDays(max(1, (int) $subscription->duration)),
                'paid_at' => now(),
            ]);
            $subscription->user->update(['is_subscribe' => 1]);
        } elseif (! in_array($paymentStatus, ['WAITING_FOR_CUSTOMER', 'WAITING_CUSTOMER_TO_VALIDATE', 'CREATED'], true)) {
            $subscription->update(['status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')]);
        }

        return response()->json(['status' => true]);
    }
}
