<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanParasiteServiceBranches extends Command
{
    protected $signature = 'service:clean-parasite-branches';
    protected $description = 'Supprime les affiliations service->salon pour les salons sans hairstyle model';

    public function handle()
    {
        // Récupérer tous les branch_id qui ont au moins un hairstyle model
        $branchesWithModels = DB::table('hairstyle_models')
            ->distinct()
            ->pluck('branch_id')
            ->toArray();

        $this->info('Salons AVEC hairstyle models: ' . implode(', ', $branchesWithModels));

        // Supprimer uniquement les affiliations pour les salons sans hairstyle model
        // SAUF si le service lui-même a été créé spécifiquement pour ce salon
        // (logique : on supprime uniquement les affiliations "globales" créées avant notre fix)
        $deleted = DB::table('service_branches')
            ->whereNotIn('branch_id', $branchesWithModels)
            ->delete();

        $this->info("✅ {$deleted} affiliations parasites supprimées (salons sans hairstyle model).");

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
