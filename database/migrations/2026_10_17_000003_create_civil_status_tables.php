<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Centres d'état civil (mairie centrale, annexes, centres secondaires).
        Schema::create('civil_centres', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('birth_acts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('centre_id')->constrained('civil_centres');
            $t->unsignedSmallInteger('registry_year')->nullable();
            $t->unsignedInteger('registry_number')->nullable(); // attribué à la validation
            $t->uuid('client_uuid')->nullable()->unique();
            $t->string('verification_code', 16)->nullable()->unique();

            // Enfant
            $t->string('child_last_name');
            $t->string('child_first_names');
            $t->string('sex', 1);                     // M | F
            $t->date('birth_date');
            $t->string('birth_date_precision', 10)->default('exact'); // exact | month | year | approx
            $t->time('birth_time')->nullable();
            $t->string('birth_place');
            $t->string('birth_place_type', 20)->nullable(); // hopital | domicile | autre

            // Père
            $t->string('father_name')->nullable();
            $t->string('father_birth_info')->nullable();
            $t->string('father_profession')->nullable();
            $t->string('father_residence')->nullable();
            $t->string('father_nationality')->nullable();

            // Mère
            $t->string('mother_name')->nullable();
            $t->string('mother_birth_info')->nullable();
            $t->string('mother_profession')->nullable();
            $t->string('mother_residence')->nullable();
            $t->string('mother_nationality')->nullable();

            // Déclaration
            $t->string('declarant_name')->nullable();
            $t->string('declarant_quality')->nullable();
            $t->date('declaration_date');
            $t->boolean('is_late_declaration')->default(false);
            $t->string('judgment_ref')->nullable(); // jugement supplétif
            $t->text('marginal_notes')->nullable();

            // Cycle de vie : draft -> validated -> (cancelled)
            $t->string('status', 20)->default('draft');
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('validated_at')->nullable();
            $t->boolean('created_offline')->default(false);
            $t->timestamps();

            $t->unique(['centre_id', 'registry_year', 'registry_number']);
            $t->index(['child_last_name', 'child_first_names']);
            $t->index('status');
        });

        // Chaque copie ou extrait délivré est tracé.
        Schema::create('act_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('birth_act_id')->constrained('birth_acts');
            $t->string('copy_type', 20)->default('extrait'); // extrait | copie_integrale
            $t->string('requester_name')->nullable();
            $t->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $t->foreignId('delivered_by')->constrained('users');
            $t->unsignedInteger('print_count')->default(0);
            $t->timestamps();
        });

        // Gabarits d'impression (le format officiel sera fourni par la mairie).
        Schema::create('act_templates', function (Blueprint $t) {
            $t->id();
            $t->string('type', 30);              // birth
            $t->string('name');
            $t->longText('html')->nullable();    // null = gabarit Blade par défaut
            $t->boolean('is_active')->default(false);
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('act_templates');
        Schema::dropIfExists('act_deliveries');
        Schema::dropIfExists('birth_acts');
        Schema::dropIfExists('civil_centres');
    }
};
