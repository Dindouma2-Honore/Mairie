<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Clôtures de caisse (Section 5.14 du cahier des charges)
        Schema::create('cash_closings', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique();
            $t->unsignedSmallInteger('year');
            $t->unsignedBigInteger('sequence');
            $t->foreignId('collector_id')->constrained('users');
            $t->date('closing_date');
            $t->unsignedInteger('receipts_count')->default(0);
            $t->unsignedBigInteger('total_amount')->default(0);      // Total général FCFA
            $t->unsignedBigInteger('cash_expected')->default(0);     // Espèces attendues calculées par le serveur
            $t->unsignedBigInteger('cash_counted')->default(0);      // Espèces déclarées et comptées
            $t->bigInteger('difference')->default(0);                // Écart (comptées - attendues)
            $t->json('breakdown')->nullable();                       // Ventilation par mode de paiement
            $t->text('notes')->nullable();                           // Explication de l'écart / observations du régisseur
            $t->string('status', 20)->default('pending');            // pending | validated | rejected
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('reviewed_at')->nullable();
            $t->text('review_notes')->nullable();                    // Commentaire ou motif de rejet du responsable financier
            $t->timestamps();

            $t->unique(['year', 'sequence']);
            $t->index(['collector_id', 'closing_date']);
            $t->index('status');
        });

        // 2. Rapprochement des reçus à une clôture
        Schema::table('receipts', function (Blueprint $t) {
            $t->foreignId('cash_closing_id')->nullable()->after('status')->constrained('cash_closings')->nullOnDelete();
        });

        // 3. Redevances & Recouvrement des contribuables (Section 5.13)
        Schema::create('taxpayer_debts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('taxpayer_id')->constrained('taxpayers')->cascadeOnDelete();
            $t->foreignId('category_id')->constrained('revenue_categories');
            $t->string('reference', 40)->unique();
            $t->string('title');
            $t->string('period', 40);
            $t->unsignedBigInteger('amount_due');
            $t->unsignedBigInteger('amount_paid')->default(0);
            $t->bigInteger('balance');
            $t->date('due_date');
            $t->string('status', 20)->default('unpaid'); // unpaid | partial | paid | cancelled
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['taxpayer_id', 'status']);
            $t->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxpayer_debts');

        Schema::table('receipts', function (Blueprint $t) {
            $t->dropForeign(['cash_closing_id']);
            $t->dropColumn('cash_closing_id');
        });

        Schema::dropIfExists('cash_closings');
    }
};
