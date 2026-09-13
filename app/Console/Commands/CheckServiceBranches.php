<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Service\Models\ServiceBranches;

class CheckServiceBranches extends Command
{
    protected $signature = 'check:service-branches';
    protected $description = 'Affiche les services et leurs salons, et affilie les services existants';

    public function handle()
    {
        $this->info('=== SERVICES ET LEURS SALONS ===');
        $services = DB::table('services')->select('id','name')->get();
        foreach($services as $s) {
            $branches = DB::table('service_branches')
                ->join('branches','branches.id','=','service_branches.branch_id')
                ->where('service_id', $s->id)
                ->pluck('branches.name')
                ->implode(', ');
            $this->line($s->id . ' | ' . $s->name . ' -> ' . ($branches ?: 'AUCUN'));
        }

        $this->info("\n=== BRANCHES ===");
        $branches = DB::table('branches')->select('id','name','manager_id')->get();
        foreach($branches as $b) {
            $this->line($b->id . ' | ' . $b->name . ' | manager_id=' . $b->manager_id);
        }

        $this->info("\n=== HAIRSTYLE MODELS PAR BRANCHE ===");
        $hairstyles = DB::table('hairstyle_models')
            ->join('branches','branches.id','=','hairstyle_models.branch_id')
            ->join('services','services.id','=','hairstyle_models.service_id')
            ->select('hairstyle_models.branch_id','branches.name as branch_name',
                     'services.id as service_id','services.name as service_name')
            ->distinct()
            ->get();

        $byBranch = $hairstyles->groupBy('branch_id');
        foreach($byBranch as $bId => $items) {
            $bname = $items->first()->branch_name;
            $snames = $items->pluck('service_name')->unique()->implode(', ');
            $this->line('Branch ' . $bId . ' (' . $bname . '): [' . $snames . ']');
        }

        return 0;
    }
}
