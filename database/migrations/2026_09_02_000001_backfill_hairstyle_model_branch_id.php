<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hairstyle_models', 'branch_id')) {
            return;
        }

        $models = DB::table('hairstyle_models')->whereNull('branch_id')->get(['id', 'service_id', 'created_by']);

        foreach ($models as $model) {
            $branchId = DB::table('service_branches')
                ->where('service_id', $model->service_id)
                ->value('branch_id');

            if (! $branchId && $model->created_by) {
                $branchId = DB::table('users')->where('id', $model->created_by)->value('branch_id');
            }

            if (! $branchId && $model->created_by) {
                $branchId = DB::table('branches')->where('manager_id', $model->created_by)->value('id');
            }

            if ($branchId) {
                DB::table('hairstyle_models')->where('id', $model->id)->update(['branch_id' => $branchId]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
