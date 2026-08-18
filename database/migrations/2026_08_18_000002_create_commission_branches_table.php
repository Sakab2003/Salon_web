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
        if (!Schema::hasTable('commission_branches')) {
            Schema::create('commission_branches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('commission_id');
                $table->unsignedBigInteger('branch_id');
                $table->timestamps();

                $table->foreign('commission_id')->references('id')->on('commissions')->onDelete('cascade');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_branches');
    }
};
