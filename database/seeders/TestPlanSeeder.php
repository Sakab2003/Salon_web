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
                'amount'     => 10,       // 10 FCFA — test réel
                'status'     => 1,
            ],
            [
                'name'       => 'Annuel (365 jours)',
                'identifier' => 'yearly',
                'type'       => 'Yearly',
                'duration'   => 365,
                'amount'     => 100,      // 100 FCFA — test réel
                'status'     => 1,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['identifier' => $plan['identifier']],
                $plan
            );
        }

        $this->command->info('✅ Plans test créés : 10 FCFA/mois, 100 FCFA/an');
    }
}
