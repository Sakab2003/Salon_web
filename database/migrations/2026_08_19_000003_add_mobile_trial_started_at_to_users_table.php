<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('users', 'mobile_trial_started_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('mobile_trial_started_at')->nullable()->after('branch_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'mobile_trial_started_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mobile_trial_started_at');
            });
        }
    }
};