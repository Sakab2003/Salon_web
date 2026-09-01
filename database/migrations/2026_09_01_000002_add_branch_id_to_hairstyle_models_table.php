<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hairstyle_models', 'branch_id')) {
            Schema::table('hairstyle_models', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->index()->after('created_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hairstyle_models', 'branch_id')) {
            Schema::table('hairstyle_models', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }
    }
};
