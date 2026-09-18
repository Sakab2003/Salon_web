<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();

            // Identité du moyen de paiement
            $table->string('name');                        // Ex: "Orange Money Burkina"
            $table->string('code')->unique();              // Ex: "orange_money_bf"
            $table->string('logo_url')->nullable();        // URL ou chemin du logo
            $table->text('description')->nullable();       // Description affichée dans l'app

            // Fournisseur technique (PulseKango, Yennegapay, CinetPay…)
            $table->string('driver');                      // Ex: "pulse_kango", "yennegapay", "cinetpay"

            // Credentials chiffrés en JSON (api_key, username, secret, base_url…)
            $table->json('config')->nullable();

            // Préfixe réseau pour valider le numéro (ex: 07,06 pour Orange BF)
            $table->string('phone_prefixes')->nullable();  // Ex: "07,06,05"

            // Gestion
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
