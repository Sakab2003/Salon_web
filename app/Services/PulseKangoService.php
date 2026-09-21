<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PulseKangoService
{
    protected string $baseUrl   = 'https://sandbox.pulse-kango.com';
    protected string $apiKey    = '';

    public function __construct(?array $credentials = null)
    {
        if ($credentials) {
            $this->baseUrl = rtrim($credentials['base_url'] ?? config('services.pulse_kango.base_url', 'https://sandbox.pulse-kango.com'), '/');
            $this->apiKey  = $credentials['api_key'] ?? config('services.pulse_kango.api_key', '') ?? '';
        } else {
            $this->baseUrl = rtrim(config('services.pulse_kango.base_url', 'https://sandbox.pulse-kango.com'), '/');
            $this->apiKey  = config('services.pulse_kango.api_key', '') ?? '';
        }

        if (!str_ends_with($this->baseUrl, '/api')) {
            $this->baseUrl .= '/api';
        }
    }

    private function headers(): array
    {
        return [
            'x-api-key'     => $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    /**
     * @param array $data { amount, phone, operator, otp, reference }
     */
    public function initiatePayment(array $data): array
    {
        try {
            $operator = strtoupper($data['operator']); // OMBF, TELBF, MOOVBF
            $payload = [
                'amount'     => (float) $data['amount'],
                'otp'        => $data['otp'] ?? '',
                'ftxn_id'    => $data['reference'],
                'operator'   => $operator,
                'transactor' => $data['phone'],
            ];

            // Pour Moov, si l'utilisateur n'a pas fourni d'OTP, on génère.
            // S'il a fourni un OTP (ex: généré via USSD manuel), on tente directement l'initiation.
            if ($operator === 'MOOVBF' && empty($payload['otp']) && empty($data['external_id'])) {
                return $this->generateMoovOtp($payload['amount'], $payload['transactor']);
            }

            if ($operator === 'MOOVBF') {
                $payload['external_id'] = $data['external_id'];
            }

            Log::info('[PulseKango] Initiate payment', ['payload' => $payload]);

            $response = Http::withoutVerifying()
                ->withHeaders($this->headers())
                ->timeout(30)
                ->post($this->baseUrl . '/v1/payment/init', $payload);

            $body = $response->json();
            Log::info('[PulseKango] Payment response', ['status' => $response->status(), 'body' => $body]);

            // La doc dit: { "code": 200, "status": "success", "data": { "message": "...", "data": { "status": "TS", ... } } }
            if ($response->successful() && isset($body['status']) && $body['status'] === 'success') {
                $txnStatus = $body['data']['data']['status'] ?? '';
                
                if ($txnStatus === 'TS' || $txnStatus === 'TP') {
                    return [
                        'success'        => true,
                        'transaction_id' => $body['data']['data']['ref'] ?? null,
                        'reference'      => $body['data']['data']['ftxn_id'] ?? $payload['ftxn_id'],
                        'status'         => $txnStatus,
                        'raw'            => $body,
                    ];
                }
            }

            // Gestion des erreurs
            $errorMessage = 'Erreur lors du paiement';

            if (!empty($body['data']['data']['external_message'])) {
                $extMsg = $body['data']['data']['external_message'];
                if (stripos($extMsg, 'OTP does not exist') !== false) {
                    $errorMessage = "Le code OTP est incorrect ou a expiré. Veuillez composer *144*4*6# pour en générer un nouveau.";
                } elseif (stripos($extMsg, 'insufficient') !== false) {
                    $errorMessage = "Solde insuffisant sur votre compte Mobile Money.";
                } else {
                    $errorMessage = $extMsg;
                }
            } elseif (isset($body['data']['data']) && is_array($body['data']['data'])) {
                $firstVal = collect($body['data']['data'])->flatten()->first();
                if ($firstVal) {
                    if (stripos($firstVal, 'amount must be at least 100') !== false) {
                        $errorMessage = "Le montant minimum pour le paiement Mobile Money est de 100 FCFA.";
                    } else {
                        $errorMessage = $firstVal;
                    }
                }
            } elseif (!empty($body['data']['message'])) {
                $errorMessage = $body['data']['message'];
            } elseif (!empty($body['message'])) {
                $errorMessage = $body['message'];
            }

            return [
                'success' => false,
                'message' => $errorMessage,
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

    public function generateMoovOtp($amount, $phone)
    {
        try {
            $payload = [
                'amount'     => (float) $amount,
                'operator'   => 'MOOVBF',
                'transactor' => $phone,
            ];

            Log::info('[PulseKango] Generate Moov OTP', ['payload' => $payload]);

            $response = Http::withoutVerifying()
                ->withHeaders($this->headers())
                ->timeout(30)
                ->post($this->baseUrl . '/v1/payment/otp-generate', $payload);

            $body = $response->json();
            Log::info('[PulseKango] Moov OTP response', ['status' => $response->status(), 'body' => $body]);

            // Supposons que l'API renvoie un success avec l'external_id
            if ($response->successful() && isset($body['status']) && $body['status'] === 'success') {
                return [
                    'success'      => false,
                    'is_otp_step'  => true,
                    'external_id'  => $body['data']['external_id'] ?? $body['external_id'] ?? null,
                    'message'      => 'Un code OTP a été envoyé sur votre numéro Moov. Veuillez le saisir et valider.',
                ];
            }

            return [
                'success' => false,
                'message' => $body['data']['message'] ?? $body['message'] ?? 'Erreur de génération OTP Moov',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur de connexion : ' . $e->getMessage(),
            ];
        }
    }

    public function checkTransactionStatus(string $ftxn_id): array
    {
        try {
            Log::info('[PulseKango] Check transaction status', ['ftxn_id' => $ftxn_id]);

            $response = Http::withoutVerifying()
                ->withHeaders($this->headers())
                ->timeout(30)
                ->post($this->baseUrl . '/v1/payment/status', [
                    'ftxn_id' => $ftxn_id
                ]);

            $body = $response->json();
            Log::info('[PulseKango] Status response', ['body' => $body]);

            if ($response->successful() && isset($body['status']) && $body['status'] === 'success') {
                $txnStatus = $body['data']['data']['status'] ?? '';
                return [
                    'success' => true,
                    'status'  => $txnStatus,
                    'paid'    => ($txnStatus === 'TS'),
                    'raw'     => $body,
                ];
            }

            return [
                'success' => false,
                'status'  => 'error',
                'paid'    => false,
                'message' => $body['data']['message'] ?? 'Transaction introuvable',
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
}