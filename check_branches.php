<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;

$branches = Branch::select('id','name','status','manager_id')->get();
echo "Total salons: " . $branches->count() . "\n";
foreach ($branches as $b) {
    echo "ID {$b->id}: '{$b->name}' status:{$b->status} manager:{$b->manager_id}\n";
}
