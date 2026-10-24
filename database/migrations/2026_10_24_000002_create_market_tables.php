<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Marchés municipaux (Annexe B & Section 5.9)
        Schema::create('markets', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->string('location')->nullable();
            $t->string('manager_name')->nullable();
            $t->string('manager_phone', 30)->nullable();
            $t->json('sectors')->nullable(); // Liste des secteurs / allées
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // 2. Emplacements, étals et boutiques
        Schema::create('market_stalls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $t->string('code', 40)->unique(); // Ex: MC-A-012, MC-BTQ-05
            $t->string('name');               // Ex: Étal 12, Boutique 5
            $t->string('sector')->nullable(); // Ex: Bloc A, Allée Centrale
            $t->string('type', 20)->default('etal'); // etal | place | boutique | hangar | magasin
            $t->string('dimensions', 50)->nullable(); // Ex: 2m x 2m, 12 m²
            $t->foreignId('tariff_id')->nullable()->constrained('tariffs')->nullOnDelete();
            $t->unsignedBigInteger('base_fee')->default(0); // Montant FCFA de référence
            $t->string('periodicity', 20)->default('journalier'); // journalier | hebdomadaire | mensuel | annuel
            $t->string('status', 20)->default('available'); // available | occupied | suspended
            $t->foreignId('current_occupant_id')->nullable()->constrained('taxpayers')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->index(['market_id', 'status']);
            $t->index('current_occupant_id');
        });

        // 3. Historique des attributions d'occupants (contrats & traçabilité)
        Schema::create('market_occupancies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stall_id')->constrained('market_stalls')->cascadeOnDelete();
            $t->foreignId('taxpayer_id')->constrained('taxpayers')->cascadeOnDelete();
            $t->string('activity')->nullable(); // Nature de l'activité exercée
            $t->date('start_date');
            $t->date('end_date')->nullable();
            $t->unsignedBigInteger('agreed_fee'); // Montant convenu en FCFA
            $t->string('authorization_ref', 50)->nullable(); // Réf décision municipale
            $t->string('status', 20)->default('active'); // active | terminated | suspended
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['stall_id', 'status']);
            $t->index(['taxpayer_id', 'status']);
        });

        // 4. Rattachement facultatif d'un reçu à un emplacement de marché
        Schema::table('receipts', function (Blueprint $t) {
            $t->foreignId('market_stall_id')->nullable()->after('taxpayer_id')->constrained('market_stalls')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $t) {
            $t->dropForeign(['market_stall_id']);
            $t->dropColumn('market_stall_id');
        });

        Schema::dropIfExists('market_occupancies');
        Schema::dropIfExists('market_stalls');
        Schema::dropIfExists('markets');
    }
};
