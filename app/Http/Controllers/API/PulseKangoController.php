<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PulseKangoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionTransactions;

/**
 * Contrôleur PulseKango — Paiement Mobile Money
 * Gère : initiation de paiement, webhook, et vérification de statut
 */
class PulseKangoController extends Controller
{
    protected PulseKangoService $pulseKango;

    public function __construct(PulseKangoService $pulseKango)
    {
        $this->pulseKango = $pulseKango;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Initier un paiement d'abonnement
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/v1/payments/pulse-kango/initiate
     * Body: { plan_id, phone }
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'phone'   => ['required', 'string'],
        ]);

        $user   = $request->user();
        $plan   = Plan::findOrFail($request->plan_id);

        // Référence unique pour cette transaction
        $reference = 'salon_' . $user->id . '_' . time();

        $result = $this->pulseKango->initiatePayment([
            'amount'      => $plan->amount,
            'currency'    => 'XOF',
            'phone'       => $request->phone,
            'description' => 'Abonnement ' . $plan->name . ' — Salon',
            'reference'   => $reference,
            'return_url'  => config('services.pulse_kango.return_url'),
            'notify_url'  => config('services.pulse_kango.notify_url'),
        ]);

        if (! $result['success']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'Erreur lors de l\'initiation du paiement.',
            ], 400);
        }

        // Créer une entrée pending dans la base de données
        $subscription = Subscription::create([
            'plan_id'    => $plan->id,
            'user_id'    => $user->id,
            'start_date' => now(),
            'end_date'   => now()->addDays($plan->duration ?? 30),
            'status'     => config('constant.SUBSCRIPTION_STATUS.PENDING', 'pending'),
            'amount'     => $plan->amount,
            'name'       => $plan->name,
            'identifier' => $plan->identifier ?? $reference,
            'type'       => $plan->type ?? 'monthly',
            'duration'   => $plan->duration ?? 30,
        ]);

        SubscriptionTransactions::create([
            'subscriptions_id' => $subscription->id,
            'user_id'          => $user->id,
            'amount'           => $plan->amount,
            'payment_status'   => 'pending',
            'payment_type'     => 'pulse_kango',
            'transaction_id'   => $result['transaction_id'] ?? $reference,
        ]);

        return response()->json([
            'status'         => true,
            'message'        => 'Paiement initié. Veuillez confirmer sur votre téléphone.',
            'payment_url'    => $result['payment_url'] ?? null,
            'transaction_id' => $result['transaction_id'] ?? null,
            'reference'      => $reference,
            'subscription_id'=> $subscription->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Webhook PulseKango (callback automatique après paiement)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/v1/payments/pulse-kango/webhook
     * Reçoit les notifications automatiques de PulseKango.
     */
    public function webhook(Request $request)
    {
        // Valider la signature du webhook
        $payload   = $request->getContent();
        $signature = $request->header('X-Pulse-Signature', '');

        if (! empty($signature) && ! $this->pulseKango->validateWebhookSignature($payload, $signature)) {
            Log::warning('[PulseKango Webhook] Signature invalide', [
                'signature' => $signature,
                'ip'        => $request->ip(),
            ]);
            return response()->json(['message' => 'Signature invalide'], 403);
        }

        $data = $request->all();
        Log::info('[PulseKango Webhook] Reçu', $data);

        $reference = $data['reference'] ?? $data['transaction_reference'] ?? null;
        $status    = strtolower($data['status'] ?? '');
        $isPaid    = in_array($status, ['success', 'paid', 'completed', 'successful']);

        if (! $reference) {
            Log::error('[PulseKango Webhook] Référence manquante', $data);
            return response()->json(['message' => 'Référence manquante'], 400);
        }

        // Trouver la transaction correspondante
        $transaction = SubscriptionTransactions::where('transaction_id', $reference)->first();

        if (! $transaction) {
            Log::warning('[PulseKango Webhook] Transaction introuvable', ['reference' => $reference]);
            return response()->json(['message' => 'Transaction non trouvée'], 404);
        }

        if ($isPaid && $transaction->payment_status !== 'paid') {
            // Marquer la transaction comme payée
            $transaction->payment_status = 'paid';
            $transaction->save();

            // Activer l'abonnement
            $subscription = Subscription::find($transaction->subscriptions_id);
            if ($subscription) {
                $subscription->status     = config('constant.SUBSCRIPTION_STATUS.ACTIVE', 'active');
                $subscription->payment_id = $transaction->id;
                $subscription->save();
            }

            // Activer l'utilisateur comme abonné
            $user = User::find($transaction->user_id);
            if ($user) {
                $user->is_subscribe = 1;
                $user->save();
            }

            Log::info('[PulseKango Webhook] Paiement confirmé et abonnement activé', [
                'reference'       => $reference,
                'subscription_id' => $subscription?->id,
                'user_id'         => $transaction->user_id,
            ]);
        } else {
            Log::info('[PulseKango Webhook] Statut non-payé reçu', ['status' => $status, 'reference' => $reference]);
        }

        return response()->json(['message' => 'Webhook traité avec succès'], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vérifier le statut d'une transaction (polling depuis le mobile)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/payments/pulse-kango/status/{reference}
     */
    public function checkStatus(Request $request, string $reference)
    {
        $result = $this->pulseKango->checkTransactionStatus($reference);

        // Si le paiement est confirmé par l'API PulseKango, on active l'abonnement localement aussi
        if ($result['paid']) {
            $transaction = SubscriptionTransactions::where('transaction_id', $reference)->first();
            if ($transaction && $transaction->payment_status !== 'paid') {
                $transaction->payment_status = 'paid';
                $transaction->save();

                $subscription = Subscription::find($transaction->subscriptions_id);
                if ($subscription) {
                    $subscription->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE', 'active');
                    $subscription->save();
                }

                $user = User::find($transaction->user_id);
                if ($user) {
                    $user->is_subscribe = 1;
                    $user->save();
                }
            }
        }

        return response()->json([
            'status'  => true,
            'paid'    => $result['paid'],
            'state'   => $result['status'],
            'message' => $result['paid'] ? 'Paiement confirmé.' : 'En attente de paiement.',
        ]);
    }
}
