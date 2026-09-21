<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'name'           => 'Orange Money',
                'code'           => 'orange_money_bf',
                'logo_url'       => '/images/payment/orange_money.png',
                'description'    => 'Payer avec Orange Money Burkina Faso',
                'driver'         => 'pulse_kango',
                'phone_prefixes' => '07,06,05',
                'is_active'      => true,
                'sort_order'     => 1,
                'config'         => null, // Utilise les credentials .env par défaut
            ],
            [
                'name'           => 'Moov Money',
                'code'           => 'moov_money_bf',
                'logo_url'       => '/images/payment/moov_money.png',
                'description'    => 'Payer avec Moov Money Burkina Faso',
                'driver'         => 'pulse_kango',
                'phone_prefixes' => '70,65,01',
                'is_active'      => true,
                'sort_order'     => 2,
                'config'         => null,
            ],
            [
                'name'           => 'Telecel Money',
                'code'           => 'telecel_money_bf',
                'logo_url'       => '/images/payment/telecel_money.png',
                'description'    => 'Payer avec Telecel Money Burkina Faso',
                'driver'         => 'pulse_kango',
                'phone_prefixes' => '62,63',
                'is_active'      => true,
                'sort_order'     => 3,
                'config'         => null,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                $gateway
            );
        }

        if ($this->command) {
            $this->command->info('✅ ' . count($gateways) . ' passerelles de paiement créées (Orange, Moov, Telecel — Burkina Faso)');
        }
    }
}
