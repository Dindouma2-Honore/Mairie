<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Actes de mariage (Format officiel bilingue Tchad - Souche, Volet 1, Volet 2)
        Schema::create('marriage_acts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('centre_id')->constrained('civil_centres');
            $t->unsignedSmallInteger('registry_year')->nullable();
            $t->unsignedInteger('registry_number')->nullable();
            $t->string('verification_code', 16)->nullable()->unique();

            // Célébration
            $t->date('celebration_date');
            $t->string('celebration_date_words')->nullable(); // En toutes lettres
            $t->string('celebration_place')->default('Mairie de Moundou');
            $t->string('officer_name')->nullable(); // Officier célébrant

            // Époux (M.)
            $t->string('husband_first_names');
            $t->string('husband_last_name');
            $t->string('husband_birth_date')->nullable();
            $t->string('husband_birth_place')->nullable();
            $t->string('husband_canton')->nullable();
            $t->string('husband_sub_prefecture')->nullable();
            $t->string('husband_father')->nullable();
            $t->string('husband_mother')->nullable();
            $t->string('husband_marital_status')->default('celibataire'); // celibataire | veuf | divorce
            $t->string('husband_previous_spouse')->nullable();
            $t->string('husband_birth_act_ref')->nullable();
            $t->string('husband_birth_act_date')->nullable();
            $t->string('husband_birth_act_by')->nullable();
            $t->string('husband_represented_by')->nullable();

            // Épouse (Mme)
            $t->string('wife_first_names');
            $t->string('wife_last_name');
            $t->string('wife_birth_date')->nullable();
            $t->string('wife_birth_place')->nullable();
            $t->string('wife_canton')->nullable();
            $t->string('wife_sub_prefecture')->nullable();
            $t->string('wife_father')->nullable();
            $t->string('wife_mother')->nullable();
            $t->string('wife_marital_status')->default('celibataire');
            $t->string('wife_previous_spouse')->nullable();
            $t->string('wife_birth_act_ref')->nullable();
            $t->string('wife_birth_act_date')->nullable();
            $t->string('wife_birth_act_by')->nullable();
            $t->string('wife_represented_by')->nullable();

            // Témoins
            $t->string('husband_witness')->nullable();
            $t->string('husband_witness_residence')->nullable();
            $t->string('wife_witness')->nullable();
            $t->string('wife_witness_residence')->nullable();

            // Stipulations particulières (Verso du formulaire)
            $t->unsignedBigInteger('dowry_cash')->default(0); // Dot en espèces
            $t->string('dowry_nature')->nullable();           // Dot en nature
            $t->string('dowry_payment_dates')->nullable();    // Dates de versement
            $t->boolean('has_marriage_contract')->default(false); // Contrat de mariage
            $t->string('contract_date')->nullable();
            $t->string('contract_notary')->nullable();

            // Cycle de vie
            $t->string('status', 20)->default('draft'); // draft | validated | cancelled
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('validated_at')->nullable();
            $t->timestamps();

            $t->unique(['centre_id', 'registry_year', 'registry_number']);
            $t->index(['husband_last_name', 'wife_last_name']);
            $t->index('status');
        });

        // 2. Actes de décès (Format officiel bilingue Tchad - Souche, Volet 1, Volet 2, Avis de mention)
        Schema::create('death_acts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('centre_id')->constrained('civil_centres');
            $t->unsignedSmallInteger('registry_year')->nullable();
            $t->unsignedInteger('registry_number')->nullable();
            $t->string('verification_code', 16)->nullable()->unique();

            // Défunt(e)
            $t->string('deceased_first_names');
            $t->string('deceased_last_name');
            $t->string('sex', 1); // M | F
            $t->date('death_date');
            $t->string('death_date_words')->nullable();
            $t->time('death_time')->nullable();
            $t->string('death_place');
            $t->string('deceased_profession')->nullable();
            $t->string('deceased_birth_date')->nullable();
            $t->string('deceased_birth_place')->nullable();
            $t->string('deceased_residence')->nullable();
            $t->string('father_name')->nullable();
            $t->string('mother_name')->nullable();
            $t->string('marital_status', 20)->default('celibataire'); // celibataire | marie | veuf | divorce
            $t->string('spouse_name')->nullable();
            $t->string('birth_act_ref')->nullable(); // Référence acte de naissance

            // Déclarant
            $t->string('declarant_name');
            $t->string('declarant_residence')->nullable();
            $t->string('declarant_quality')->nullable(); // Parenté ou statut
            $t->date('declaration_date');

            // Médical & Inhumation
            $t->string('medical_certificate_ref')->nullable();
            $t->string('burial_place')->nullable();
            $t->date('burial_date')->nullable();

            // Cycle de vie
            $t->string('status', 20)->default('draft'); // draft | validated | cancelled
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('validated_at')->nullable();
            $t->timestamps();

            $t->unique(['centre_id', 'registry_year', 'registry_number']);
            $t->index(['deceased_last_name', 'deceased_first_names']);
            $t->index('status');
        });

        // 3. Guichet de demandes d'actes et délivrance (Annexe A.4)
        Schema::create('civil_requests', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->unique(); // Ex: ACT-2026-0001
            $t->string('act_type', 20);            // birth | marriage | death
            $t->string('document_type', 30);       // extrait | copie_integrale
            $t->unsignedSmallInteger('copies_count')->default(1);

            // Lien direct vers l'acte s'il existe dans le système
            $t->foreignId('birth_act_id')->nullable()->constrained('birth_acts')->nullOnDelete();
            $t->foreignId('marriage_act_id')->nullable()->constrained('marriage_acts')->nullOnDelete();
            $t->foreignId('death_act_id')->nullable()->constrained('death_acts')->nullOnDelete();
            $t->string('external_act_ref')->nullable(); // Si recherche sur anciens registres papier

            // Demandeur
            $t->string('requester_name');
            $t->string('requester_phone', 30)->nullable();
            $t->string('requester_id_number', 50)->nullable(); // N° CNI ou passeport
            $t->string('person_concerned'); // Nom de la personne concernée par l'acte

            // Régie financière
            $t->unsignedBigInteger('amount')->default(0);
            $t->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();

            // Suivi et cycle de traitement
            $t->string('status', 20)->default('pending'); // pending | processing | ready | delivered | rejected
            $t->date('request_date');
            $t->dateTime('ready_at')->nullable();
            $t->dateTime('delivered_at')->nullable();
            $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->index(['act_type', 'status']);
            $t->index('request_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('civil_requests');
        Schema::dropIfExists('death_acts');
        Schema::dropIfExists('marriage_acts');
    }
};
