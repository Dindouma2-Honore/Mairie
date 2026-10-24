<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs exigés par le formulaire officiel d'acte de naissance (Volets N°1 et N°2) :
 * rue, et lieu / date de naissance des parents séparés.
 * Les dates des parents sont du texte libre : elles sont souvent approximatives (« vers 1985 »).
 * Les anciennes colonnes *_birth_info sont conservées (utilisées en repli à l'impression).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('birth_acts', function (Blueprint $t) {
            $t->string('birth_street', 160)->nullable()->after('birth_place_type');
            $t->string('father_birth_place', 160)->nullable()->after('father_birth_info');
            $t->string('father_birth_date', 60)->nullable()->after('father_birth_place');
            $t->string('mother_birth_place', 160)->nullable()->after('mother_birth_info');
            $t->string('mother_birth_date', 60)->nullable()->after('mother_birth_place');
        });
    }

    public function down(): void
    {
        Schema::table('birth_acts', function (Blueprint $t) {
            $t->dropColumn(['birth_street', 'father_birth_place', 'father_birth_date', 'mother_birth_place', 'mother_birth_date']);
        });
    }
};
