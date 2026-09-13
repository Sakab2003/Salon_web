<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('hairstyle_models', function (Blueprint $table) {
            $table->string('target_audience')->nullable()->after('name');
            $table->unsignedBigInteger('service_id')->nullable()->change();
            $table->unsignedBigInteger('commission_id')->nullable()->change();
            $table->text('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hairstyle_models', function (Blueprint $table) {
            $table->dropColumn('target_audience');
        });
    }
};
