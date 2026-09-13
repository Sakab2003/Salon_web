<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AffiliateServicesToBranches extends Command
{
    protected $signature = 'service:affiliate-branches';
    protected $description = 'Affilie les services existants aux salons selon leurs hairstyle models';

    public function handle()
    {
        // Récupérer toutes les associations service -> branch via hairstyle_models
        $hairstyles = DB::table('hairstyle_models')
            ->join('branches','branches.id','=','hairstyle_models.branch_id')
            ->join('services','services.id','=','hairstyle_models.service_id')
            ->select('hairstyle_models.branch_id','branches.name as branch_name',
                     'services.id as service_id','services.name as service_name',
                     'services.default_price','services.duration_min')
            ->distinct()
            ->get();

        $count = 0;
        foreach ($hairstyles as $h) {
            // Vérifier si l'affiliation existe déjà
            $exists = DB::table('service_branches')
                ->where('service_id', $h->service_id)
                ->where('branch_id', $h->branch_id)
                ->exists();

            if (!$exists) {
                DB::table('service_branches')->insert([
                    'service_id'    => $h->service_id,
                    'branch_id'     => $h->branch_id,
                    'service_price' => $h->default_price,
                    'duration_min'  => $h->duration_min,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $this->info("✓ Affilié: [{$h->service_name}] → [{$h->branch_name}]");
                $count++;
            } else {
                $this->line("  Déjà affilié: [{$h->service_name}] → [{$h->branch_name}]");
            }
        }

        // Pour les services déjà affiliés à TOUS les salons (1-5),
        // supprimer les affiliations parasites vers les salons sans hairstyle model pour ce service
        // (ex: Coupe Femme est dans tous les salons 1-5 mais aucun hairstyle model n'existe pour salons 4,5 pour ce service)
        // On ne touche pas à ceux qui sont déjà corrects selon les hairstyle models

        $this->info("\n✅ {$count} nouvelles affiliations créées.");

        // Vérification finale
        $this->info("\n=== RÉSULTAT FINAL ===");
        $services = DB::table('services')->select('id','name')->get();
        foreach ($services as $s) {
            $bnames = DB::table('service_branches')
                ->join('branches','branches.id','=','service_branches.branch_id')
                ->where('service_id', $s->id)
                ->pluck('branches.name')
                ->implode(', ');
            if ($bnames) {
                $this->line($s->id . ' | ' . $s->name . ' -> ' . $bnames);
            }
        }

        return 0;
    }
}
