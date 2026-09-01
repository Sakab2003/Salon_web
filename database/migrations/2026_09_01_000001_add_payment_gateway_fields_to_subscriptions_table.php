<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'payment_transaction_id')) {
                $table->string('payment_transaction_id')->nullable()->unique()->after('payment_id');
            }
            if (! Schema::hasColumn('subscriptions', 'payment_provider')) {
                $table->string('payment_provider')->nullable()->after('payment_transaction_id');
            }
            if (! Schema::hasColumn('subscriptions', 'payment_url')) {
                $table->text('payment_url')->nullable()->after('payment_provider');
            }
            if (! Schema::hasColumn('subscriptions', 'payment_payload')) {
                $table->longText('payment_payload')->nullable()->after('payment_url');
            }
            if (! Schema::hasColumn('subscriptions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            foreach (['paid_at', 'payment_payload', 'payment_url', 'payment_provider', 'payment_transaction_id'] as $column) {
                if (Schema::hasColumn('subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
