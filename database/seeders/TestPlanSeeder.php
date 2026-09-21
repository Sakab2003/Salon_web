<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Subscriptions\Models\Plan;

class TestPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'       => 'Mensuel (30 jours)',
                'identifier' => 'monthly',
                'type'       => 'Monthly',
                'duration'   => 30,
                'amount'     => 100,      // 100 FCFA/mois
                'status'     => 1,
            ],
            [
                'name'       => 'Annuel (365 jours)',
                'identifier' => 'yearly',
                'type'       => 'Yearly',
                'duration'   => 365,
                'amount'     => 960,      // 960 FCFA/an (20% de réduction)
                'discount_percentage' => '20.00',
                'status'     => 1,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['identifier' => $plan['identifier']],
                $plan
            );
        }

        if ($this->command) {
            $this->command->info('✅ Plans test créés : 100 FCFA/mois, 960 FCFA/an');
        }
    }
}
