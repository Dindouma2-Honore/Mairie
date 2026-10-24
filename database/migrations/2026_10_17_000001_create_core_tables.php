<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->string('code', 20)->unique();
            $t->string('name');
            $t->string('head_name')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 50)->unique();
            $t->string('name');
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 80)->unique();
            $t->string('module', 40)->index();
            $t->string('label');
        });

        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $t->string('phone', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->boolean('must_change_password')->default(false);
        });

        // Journal d'audit : ajout uniquement, jamais modifié.
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('event', 80)->index();
            $t->string('subject_type', 120)->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
            $t->index(['subject_type', 'subject_id']);
        });

        // Séquences de numérotation (reçus, actes, matricules…).
        Schema::create('numbering_sequences', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80);
            $t->unsignedSmallInteger('year')->default(0);
            $t->unsignedBigInteger('last_number')->default(0);
            $t->unique(['key', 'year']);
        });

        // Lots de numéros réservés à un collecteur (carnet à souches numérique, usage hors ligne).
        Schema::create('numbering_blocks', function (Blueprint $t) {
            $t->id();
            $t->string('key', 80);
            $t->unsignedSmallInteger('year')->default(0);
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('start_number');
            $t->unsignedBigInteger('end_number');
            $t->timestamp('created_at')->useCurrent();
            $t->index(['key', 'year', 'user_id']);
        });

        // Idempotence de la synchronisation hors ligne.
        Schema::create('sync_receipts', function (Blueprint $t) {
            $t->id();
            $t->uuid('client_uuid')->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 60);
            $t->json('result');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_receipts');
        Schema::dropIfExists('numbering_blocks');
        Schema::dropIfExists('numbering_sequences');
        Schema::dropIfExists('audit_logs');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('service_id');
            $t->dropColumn(['phone', 'is_active', 'last_login_at', 'must_change_password']);
        });
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('services');
    }
};
