<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hairstyle_models', function (Blueprint $table) {
            if (!Schema::hasColumn('hairstyle_models', 'commission_id')) {
                $table->unsignedBigInteger('commission_id')->nullable()->after('service_id');
                $table->foreign('commission_id')->references('id')->on('commissions')->nullOnDelete();
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'commission_id')) {
                $table->unsignedBigInteger('commission_id')->nullable()->after('category_id');
                $table->foreign('commission_id')->references('id')->on('commissions')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hairstyle_models', function (Blueprint $table) {
            if (Schema::hasColumn('hairstyle_models', 'commission_id')) {
                $table->dropForeign(['commission_id']);
                $table->dropColumn('commission_id');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'commission_id')) {
                $table->dropForeign(['commission_id']);
                $table->dropColumn('commission_id');
            }
        });
    }
};
