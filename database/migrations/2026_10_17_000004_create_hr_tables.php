<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('matricule', 30)->unique();
            $t->string('last_name');
            $t->string('first_names');
            $t->string('sex', 1)->nullable();
            $t->date('birth_date')->nullable();
            $t->string('birth_place')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('position')->nullable();   // poste
            $t->string('grade')->nullable();
            $t->string('status', 20)->default('contractuel'); // fonctionnaire | contractuel | journalier | stagiaire
            $t->date('hire_date')->nullable();
            $t->date('end_date')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['last_name', 'first_names']);
        });

        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid')->nullable()->unique();
            $t->foreignId('agent_id')->constrained('agents');
            $t->string('type', 20); // conge_annuel | maladie | maternite | permission | autre
            $t->date('start_date');
            $t->date('end_date');
            $t->unsignedSmallInteger('days');
            $t->string('reason')->nullable();
            $t->string('status', 20)->default('pending'); // pending | approved | rejected
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('decided_at')->nullable();
            $t->string('decision_note')->nullable();
            $t->timestamps();
            $t->index(['agent_id', 'start_date']);
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('agents');
    }
};
