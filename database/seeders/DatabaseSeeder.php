<?php

namespace Database\Seeders;

use App\Models\CivilCentre;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de démarrage SANS données métier inventées :
     * aucun tarif, aucune catégorie de recette (à saisir selon les textes de la mairie).
     * Pour des données de démonstration : php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        Service::firstOrCreate(['code' => 'SG'], ['name' => 'Secrétariat général']);
        CivilCentre::firstOrCreate(['code' => 'CENTRAL'], ['name' => 'Centre principal d’état civil']);

        $email = env('ADMIN_EMAIL', 'admin@mairie360.local');

        if (! User::where('email', $email)->exists()) {
            $password = env('password') ?: Str::password(8, symbols: false);

            $admin = User::create([
                'name' => 'Administrateur',
                'email' => $email,
                'password' => $password,
                'must_change_password' => true,
            ]);
            $admin->roles()->sync(Role::whereIn('slug', ['admin_technique', 'admin_municipal'])->pluck('id'));

            $this->command?->warn("Compte administrateur créé : {$email}");
            $this->command?->warn("Mot de passe temporaire : {$password}  (à changer à la première connexion)");
        }
    }
}
