<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Role;
use App\Models\RevenueCategory;
use App\Models\Service;
use App\Models\Taxpayer;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * DONNÉES DE DÉMONSTRATION UNIQUEMENT — montants et libellés fictifs.
 * Ne jamais exécuter en production : les tarifs réels viennent de la mairie.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder refusé en production.');

            return;
        }

        $this->call(DatabaseSeeder::class);

        $finance = Service::firstOrCreate(['code' => 'FIN'], ['name' => 'Service financier']);
        $civil = Service::firstOrCreate(['code' => 'EC'], ['name' => 'État civil']);

        $categories = [
            'DEMO-MARCHE' => ['[DÉMO] Droits de place — marché', true, [['Étal journalier', 100], ['Étal mensuel', 2500]]],
            'DEMO-ACTE' => ['[DÉMO] Délivrance d’actes', false, [['Extrait (démo)', 500]]],
        ];
        foreach ($categories as $code => [$name, $custom, $tariffs]) {
            $cat = RevenueCategory::firstOrCreate(['code' => $code], ['name' => $name, 'allow_custom_amount' => $custom]);
            foreach ($tariffs as [$label, $amount]) {
                $cat->tariffs()->firstOrCreate(['label' => $label], ['amount' => $amount, 'effective_from' => now()->startOfYear()]);
            }
        }

        Taxpayer::firstOrCreate(['reference' => 'DEMO-001'], ['name' => '[DÉMO] Commerçant exemple', 'quarter' => 'Quartier exemple', 'type' => 'personne']);

        $accounts = [
            ['regisseur@demo.local', 'Collecteur Démo', 'regisseur', $finance],
            ['officier@demo.local', 'Officier Démo', 'officier_etat_civil', $civil],
            ['agentec@demo.local', 'Agent État civil Démo', 'agent_etat_civil', $civil],
            ['rh@demo.local', 'RH Démo', 'rh', null],
            ['direction@demo.local', 'Direction Démo', 'direction', null],
        ];
        foreach ($accounts as [$email, $name, $role, $service]) {
            $user = User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => 'demo-Passw0rd!', 'service_id' => $service?->id]);
            $user->roles()->syncWithoutDetaching(Role::where('slug', $role)->pluck('id'));
        }

        Agent::firstOrCreate(['matricule' => 'DEMO-001'], ['last_name' => 'Exemple', 'first_names' => 'Agent', 'status' => 'contractuel', 'service_id' => $civil->id]);

        $this->command?->info('Démo prête. Comptes @demo.local, mot de passe : demo-Passw0rd!');
    }
}
