<?php

namespace Modules\Subscriptions\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name'        => 'Mensuel',
                'type'        => 'monthly',
                'duration'    => 30,
                'amount'      => 10000,
                'identifier'  => 'monthly_10000',
                'status'      => 1,
                'trial_period'=> 3,
                'description' => 'Abonnement mensuel — accès complet 30 jours pour 10 000 FCFA.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Annuel',
                'type'        => 'yearly',
                'duration'    => 365,
                'amount'      => 100000,
                'identifier'  => 'yearly_100000',
                'status'      => 1,
                'trial_period'=> 3,
                'description' => 'Abonnement annuel — accès complet 365 jours pour 100 000 FCFA. Économisez 33%!',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plan')->updateOrInsert(
                ['identifier' => $plan['identifier']],
                $plan
            );
        }

        $this->command->info('✅ Plans d\'abonnement créés : Mensuel (10 000 FCFA) + Annuel (100 000 FCFA)');
    }
}
