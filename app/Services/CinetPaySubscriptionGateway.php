<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CinetPaySubscriptionGateway
{
    private const PAYMENT_URL = 'https://api-checkout.cinetpay.com/v2/payment';
    private const CHECK_URL = 'https://api-checkout.cinetpay.com/v2/payment/check';

    public function isConfigured(): bool
    {
        return filled(config('services.cinetpay.api_key')) && filled(config('services.cinetpay.site_id'));
    }

    public function initialize(User $user, string $transactionId, int $amount): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Le paiement par abonnement n’est pas encore configuré. Ajoutez les identifiants CinetPay dans le fichier .env.');
        }

        try {
            $response = Http::acceptJson()->timeout(20)->post(self::PAYMENT_URL, [
                'apikey' => config('services.cinetpay.api_key'),
                'site_id' => config('services.cinetpay.site_id'),
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => config('services.cinetpay.currency', 'XOF'),
                'description' => 'Abonnement Salon',
                'notify_url' => route('api.v1.payments.webhook'),
                'return_url' => config('services.cinetpay.return_url', config('app.url').'/payment-return'),
                'channels' => 'ALL',
                'lang' => 'fr',
                'metadata' => json_encode(['user_id' => $user->id, 'type' => 'subscription']),
                'customer_id' => (string) $user->id,
                'customer_name' => $user->last_name,
                'customer_surname' => $user->first_name,
                'customer_email' => $user->email,
                'customer_phone_number' => $user->mobile,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Impossible de joindre la passerelle de paiement. Réessayez dans quelques instants.');
        }

        $payload = $response->json() ?: [];
        if (! $response->successful() || (string) data_get($payload, 'code') !== '201' || ! data_get($payload, 'data.payment_url')) {
            throw new RuntimeException(data_get($payload, 'description') ?: 'La passerelle de paiement a refusé l’initialisation.');
        }

        return $payload;
    }

    public function verify(string $transactionId): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('La vérification CinetPay n’est pas configurée.');
        }

        try {
            $response = Http::acceptJson()->timeout(20)->post(self::CHECK_URL, [
                'apikey' => config('services.cinetpay.api_key'),
                'site_id' => config('services.cinetpay.site_id'),
                'transaction_id' => $transactionId,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Impossible de vérifier le paiement pour le moment.');
        }

        return $response->json() ?: [];
    }
}
