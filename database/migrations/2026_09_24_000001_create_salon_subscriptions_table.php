<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('salon_subscriptions');

        Schema::create('salon_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('salon_code', 30)->unique();
            $table->string('device_id', 120)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('device_name', 190)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->timestamp('install_date')->nullable();
            $table->timestamp('trial_end_date')->nullable();
            $table->timestamp('subscription_start_date')->nullable();
            $table->timestamp('subscription_end_date')->nullable();
            $table->enum('status', ['trial', 'active', 'expired', 'cancelled'])->default('trial')->index();
            $table->string('subscription_type', 50)->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_method', 100)->nullable();
            $table->string('payment_reference', 190)->nullable();
            $table->json('device_info')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['subscription_end_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_subscriptions');
    }
};
