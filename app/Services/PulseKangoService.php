<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'intégration PulseKango — Unified Mobile Money Gateway
 * Production : https://sandbox.pulse-kango.com (mode sandbox/prod selon config)
 *
 * Documentation API : https://sandbox.pulse-kango.com/
 */
class PulseKangoService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $username;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.pulse_kango.base_url', 'https://sandbox.pulse-kango.com'), '/');
        $this->apiKey   = config('services.pulse_kango.api_key', '');
        $this->username = config('services.pulse_kango.username', '');
        $this->secret   = config('services.pulse_kango.secret', '');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Headers communs
    // ─────────────────────────────────────────────────────────────────────────

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'X-Username'    => $this->username,
            'X-Secret'      => $this->secret,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Initier un paiement (Mobile Money / Carte)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Initie une transaction de paiement PulseKango.
     *
     * @param array $data {
     *   amount        : float   — Montant en FCFA
     *   currency      : string  — Devise (ex: XOF)
     *   phone         : string  — Numéro de téléphone du payeur
     *   description   : string  — Description de la transaction
     *   reference     : string  — Référence interne unique
     *   return_url    : string  — URL de retour après paiement
     *   notify_url    : string  — URL de webhook
     * }
     * @return array
     */
    public function initiatePayment(array $data): array
    {
        try {
            $payload = [
                'amount'      => (float) $data['amount'],
                'currency'    => $data['currency'] ?? 'XOF',
                'phone'       => $data['phone'] ?? '',
                'description' => $data['description'] ?? 'Abonnement Salon',
                'reference'   => $data['reference'] ?? uniqid('salon_'),
                'return_url'  => $data['return_url'] ?? config('services.pulse_kango.return_url'),
                'notify_url'  => $data['notify_url'] ?? config('services.pulse_kango.notify_url'),
            ];

            Log::info('[PulseKango] Initiate payment', ['payload' => $payload]);

            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/payment/initiate', $payload);

            $body = $response->json();

            Log::info('[PulseKango] Payment response', ['status' => $response->status(), 'body' => $body]);

            if ($response->successful()) {
                return [
                    'success'        => true,
                    'transaction_id' => $body['transaction_id'] ?? $body['id'] ?? null,
                    'payment_url'    => $body['payment_url'] ?? $body['checkout_url'] ?? null,
                    'reference'      => $body['reference'] ?? $payload['reference'],
                    'raw'            => $body,
                ];
            }

            return [
                'success' => false,
                'message' => $body['message'] ?? 'Erreur lors de l\'initiation du paiement.',
                'raw'     => $body,
            ];
        } catch (\Exception $e) {
            Log::error('[PulseKango] Exception during payment initiation', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Erreur de connexion au service de paiement : ' . $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vérifier le statut d'une transaction
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Vérifie le statut d'une transaction par référence.
     *
     * @param string $reference Référence de la transaction
     * @return array
     */
    public function checkTransactionStatus(string $reference): array
    {
        try {
            Log::info('[PulseKango] Check transaction status', ['reference' => $reference]);

            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get($this->baseUrl . '/api/v1/payment/status/' . $reference);

            $body = $response->json();

            Log::info('[PulseKango] Status response', ['body' => $body]);

            return [
                'success' => $response->successful(),
                'status'  => $body['status'] ?? 'unknown',
                'paid'    => in_array(strtolower($body['status'] ?? ''), ['success', 'paid', 'completed']),
                'raw'     => $body,
            ];
        } catch (\Exception $e) {
            Log::error('[PulseKango] Exception checking status', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'status'  => 'error',
                'paid'    => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Valider la signature du webhook
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Valide la signature du webhook PulseKango pour sécuriser les callbacks.
     *
     * @param string $payload   Corps brut de la requête
     * @param string $signature Signature reçue dans le header
     * @return bool
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, $this->secret);
        return hash_equals($expected, $signature);
    }
}
