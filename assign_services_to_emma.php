<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Branch;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;

echo "Attribution des services admin à Emma Roberts...\n\n";

// Trouver Emma Roberts
$emma = User::where('email', 'emma.roberts@glamourcuts.co.uk')->first();
if (!$emma) {
    echo "Emma Roberts non trouvée!\n";
    exit(1);
}
echo "Emma Roberts ID: {$emma->id}\n";

// Trouver Glamour Cuts branch
$glamourBranch = Branch::where('name', 'Glamour Cuts')->first();
if (!$glamourBranch) {
    echo "Glamour Cuts branch non trouvée!\n";
    exit(1);
}
echo "Glamour Cuts Branch ID: {$glamourBranch->id}\n";

// Assigner Emma comme manager de Glamour Cuts
$glamourBranch->manager_id = $emma->id;
$glamourBranch->save();
echo "Emma Roberts assignée comme manager de Glamour Cuts\n";

// Attribuer tous les services sans branch à Glamour Cuts
$services = Service::whereDoesntHave('branches', function($query) use ($glamourBranch) {
    $query->where('branch_id', $glamourBranch->id);
})->get();

$count = 0;
foreach ($services as $service) {
    ServiceBranches::firstOrCreate([
        'service_id' => $service->id,
        'branch_id' => $glamourBranch->id,
    ], [
        'service_price' => $service->default_price,
        'duration_min' => $service->duration_min,
    ]);
    $count++;
}

echo "Services attribués: {$count}\n";
echo "Terminé!\n";
