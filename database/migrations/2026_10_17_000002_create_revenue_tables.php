<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenue_categories', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->unique();
            $t->string('name');
            $t->string('description')->nullable();
            $t->boolean('allow_custom_amount')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // Tarifs versionnés : jamais modifiés, on en crée un nouveau avec une date d'effet.
        Schema::create('tariffs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained('revenue_categories')->cascadeOnDelete();
            $t->string('label');
            $t->unsignedBigInteger('amount'); // FCFA, sans décimales
            $t->date('effective_from');
            $t->date('effective_to')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['category_id', 'effective_from']);
        });

        Schema::create('taxpayers', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 40)->nullable()->unique();
            $t->string('type', 20)->default('personne'); // personne | entreprise
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->string('quarter')->nullable();   // quartier / repère (pas d'adresse postale formelle)
            $t->string('activity')->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('name');
        });

        Schema::create('receipts', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->unsignedSmallInteger('number_year');
            $t->unsignedBigInteger('number_seq');
            $t->uuid('client_uuid')->nullable()->unique();
            $t->foreignId('category_id')->constrained('revenue_categories');
            $t->foreignId('tariff_id')->nullable()->constrained('tariffs')->nullOnDelete();
            $t->foreignId('taxpayer_id')->nullable()->constrained('taxpayers')->nullOnDelete();
            $t->string('payer_name')->nullable();
            $t->string('description')->nullable();
            $t->unsignedInteger('quantity')->default(1);
            $t->unsignedBigInteger('unit_amount');
            $t->unsignedBigInteger('amount');
            $t->string('payment_method', 20)->default('espece');
            $t->string('transaction_ref', 80)->nullable();
            $t->string('status', 20)->default('paid'); // paid | cancelled
            $t->foreignId('collected_by')->constrained('users');
            $t->dateTime('collected_at');
            $t->boolean('created_offline')->default(false);
            $t->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('cancelled_at')->nullable();
            $t->string('cancel_reason')->nullable();
            $t->timestamps();
            $t->unique(['number_year', 'number_seq']);
            $t->index('collected_at');
            $t->index(['status', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('taxpayers');
        Schema::dropIfExists('tariffs');
        Schema::dropIfExists('revenue_categories');
    }
};
