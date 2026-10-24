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

        $taxpayer1 = Taxpayer::firstOrCreate(['reference' => 'DEMO-001'], [
            'name' => 'Boutique Moundou Progrès',
            'quarter' => 'Dombao',
            'type' => 'entreprise',
            'activity' => 'Commerce général et alimentation',
            'phone' => '+235 66 12 34 56',
            'latitude' => 8.5667,
            'longitude' => 16.0833,
        ]);

        $taxpayer2 = Taxpayer::firstOrCreate(['reference' => 'DEMO-002'], [
            'name' => 'Moussa Mahamat Djibrine',
            'quarter' => 'Gueldjem',
            'type' => 'personne',
            'activity' => 'Transporteur urbain (clando)',
            'phone' => '+235 99 87 65 43',
        ]);

        $accounts = [
            ['regisseur@demo.local', 'Collecteur Démo', 'regisseur', $finance],
            ['financier@demo.local', 'Responsable Financier Démo', 'resp_financier', $finance],
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

        $marcheCat = RevenueCategory::where('code', 'DEMO-MARCHE')->first();
        if ($marcheCat && $taxpayer1 && $taxpayer2) {
            \App\Models\TaxpayerDebt::firstOrCreate(['reference' => 'AVIS-2026-00001'], [
                'taxpayer_id' => $taxpayer1->id,
                'category_id' => $marcheCat->id,
                'title' => 'Redevance annuelle droit de place boutique',
                'period' => 'Exercice 2026',
                'amount_due' => 30000,
                'amount_paid' => 10000,
                'balance' => 20000,
                'due_date' => now()->addDays(10),
                'status' => 'partial',
            ]);

            \App\Models\TaxpayerDebt::firstOrCreate(['reference' => 'AVIS-2026-00002'], [
                'taxpayer_id' => $taxpayer2->id,
                'category_id' => $marcheCat->id,
                'title' => 'Taxe de stationnement mensuelle',
                'period' => 'Septembre 2026',
                'amount_due' => 5000,
                'amount_paid' => 0,
                'balance' => 5000,
                'due_date' => now()->subDays(5),
                'status' => 'unpaid',
            ]);
        }

        // Marchés municipaux (Moundou)
        $central = \App\Models\Market::firstOrCreate(['code' => 'MC-MOUNDOU'], [
            'name' => 'Marché Central de Moundou',
            'location' => 'Centre Commercial / Dombao',
            'manager_name' => 'Brahim Mahamat (Régisseur)',
            'manager_phone' => '+235 66 55 44 33',
            'sectors' => ['Allée A (Tissus)', 'Allée B (Alimentation)', 'Bloc Boutiques Nord', 'Hangar Céréales'],
            'latitude' => 8.5667,
            'longitude' => 16.0833,
            'is_active' => true,
        ]);

        $gueldjem = \App\Models\Market::firstOrCreate(['code' => 'MG-GUELDJEM'], [
            'name' => 'Marché de Gueldjem',
            'location' => 'Quartier Gueldjem',
            'manager_name' => 'Saleh Ousmane',
            'manager_phone' => '+235 99 22 11 00',
            'sectors' => ['Secteur Maraîcher', 'Allée Poissonnerie'],
            'is_active' => true,
        ]);

        if ($central && $taxpayer1) {
            $stall1 = \App\Models\MarketStall::firstOrCreate(['code' => 'MC-A-01'], [
                'market_id' => $central->id,
                'name' => 'Étal Allée A - N°01',
                'sector' => 'Allée A (Tissus)',
                'type' => 'etal',
                'dimensions' => '2m x 2m',
                'base_fee' => 500,
                'periodicity' => 'journalier',
                'status' => 'occupied',
                'current_occupant_id' => $taxpayer1->id,
            ]);

            \App\Models\MarketOccupancy::firstOrCreate(['stall_id' => $stall1->id, 'taxpayer_id' => $taxpayer1->id], [
                'activity' => 'Vente de tissus et pagnes',
                'start_date' => now()->startOfYear(),
                'agreed_fee' => 500,
                'status' => 'active',
            ]);

            \App\Models\MarketStall::firstOrCreate(['code' => 'MC-A-02'], [
                'market_id' => $central->id,
                'name' => 'Étal Allée A - N°02',
                'sector' => 'Allée A (Tissus)',
                'type' => 'etal',
                'dimensions' => '2m x 2m',
                'base_fee' => 500,
                'periodicity' => 'journalier',
                'status' => 'available',
            ]);

            \App\Models\MarketStall::firstOrCreate(['code' => 'MC-BTQ-01'], [
                'market_id' => $central->id,
                'name' => 'Boutique Bloc Nord - N°01',
                'sector' => 'Bloc Boutiques Nord',
                'type' => 'boutique',
                'dimensions' => '4m x 4m (16 m²)',
                'base_fee' => 25000,
                'periodicity' => 'mensuel',
                'status' => 'occupied',
                'current_occupant_id' => $taxpayer1->id,
            ]);

            \App\Models\MarketStall::firstOrCreate(['code' => 'MC-BTQ-02'], [
                'market_id' => $central->id,
                'name' => 'Boutique Bloc Nord - N°02',
                'sector' => 'Bloc Boutiques Nord',
                'type' => 'boutique',
                'dimensions' => '4m x 4m (16 m²)',
                'base_fee' => 25000,
                'periodicity' => 'mensuel',
                'status' => 'available',
            ]);
        }

        $this->command?->info('Démo prête. Comptes @demo.local, mot de passe : demo-Passw0rd!');
    }
}
