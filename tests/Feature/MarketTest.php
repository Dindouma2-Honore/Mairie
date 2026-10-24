<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\MarketOccupancy;
use App\Models\MarketStall;
use App\Models\RevenueCategory;
use App\Models\Role;
use App\Models\Taxpayer;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regisseur;
    private Taxpayer $commercant1;
    private Taxpayer $commercant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Admin Municipal',
            'email' => 'admin.municipal@test.local',
            'password' => 'secret123',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'admin_municipal')->first());

        $this->regisseur = User::create([
            'name' => 'Collecteur Marches',
            'email' => 'collecteur.marches@test.local',
            'password' => 'secret123',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $this->regisseur->roles()->attach(Role::where('slug', 'regisseur')->first());

        $this->commercant1 = Taxpayer::create([
            'reference' => 'COM-01',
            'name' => 'Fatime Saleh',
            'quarter' => 'Dombao',
            'type' => 'personne',
            'activity' => 'Commerçante de céréales',
        ]);

        $this->commercant2 = Taxpayer::create([
            'reference' => 'COM-02',
            'name' => 'Adoum Mahamat',
            'quarter' => 'Gueldjem',
            'type' => 'personne',
            'activity' => 'Boucher',
        ]);
    }

    public function test_market_creation_and_listing(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('markets.store'), [
            'code' => 'MC-MOUNDOU',
            'name' => 'Marché Central de Moundou',
            'location' => 'Centre-ville',
            'manager_name' => 'Régisseur Central',
            'manager_phone' => '+235 66 11 22 33',
            'sectors_list' => 'Allée A, Allée B, Bloc Boucherie',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('markets', ['code' => 'MC-MOUNDOU']);

        $market = Market::where('code', 'MC-MOUNDOU')->first();
        $this->assertCount(3, $market->sectors);
        $this->assertContains('Allée A', $market->sectors);

        $listResponse = $this->get(route('markets.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Marché Central de Moundou');
    }

    public function test_stall_creation_and_occupancy_rate(): void
    {
        $this->actingAs($this->admin);

        $market = Market::create([
            'code' => 'MG-01',
            'name' => 'Marché de Gueldjem',
            'location' => 'Gueldjem',
            'is_active' => true,
        ]);

        $s1 = MarketStall::create([
            'market_id' => $market->id,
            'code' => 'MG-A-01',
            'name' => 'Étal 1',
            'sector' => 'Allée 1',
            'type' => 'etal',
            'base_fee' => 500,
            'periodicity' => 'journalier',
            'status' => 'available',
        ]);

        $s2 = MarketStall::create([
            'market_id' => $market->id,
            'code' => 'MG-A-02',
            'name' => 'Étal 2',
            'sector' => 'Allée 1',
            'type' => 'etal',
            'base_fee' => 500,
            'periodicity' => 'journalier',
            'status' => 'occupied',
            'current_occupant_id' => $this->commercant1->id,
        ]);

        $this->assertEquals(2, $market->totalStalls());
        $this->assertEquals(1, $market->occupiedStallsCount());
        $this->assertEquals(1, $market->availableStallsCount());
        $this->assertEquals(50.0, $market->occupancyRate());

        $showResponse = $this->get(route('markets.show', $market));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('MG-A-01');
        $showResponse->assertSee('MG-A-02');
        $showResponse->assertSee('Fatime Saleh');
    }

    public function test_assign_occupant_and_prevent_double_occupancy(): void
    {
        $this->actingAs($this->admin);

        $market = Market::create([
            'code' => 'MC-01',
            'name' => 'Marché Central',
            'is_active' => true,
        ]);

        $stall = MarketStall::create([
            'market_id' => $market->id,
            'code' => 'MC-BTQ-01',
            'name' => 'Boutique 1',
            'type' => 'boutique',
            'base_fee' => 20000,
            'periodicity' => 'mensuel',
            'status' => 'available',
        ]);

        // 1. Première attribution à Commerçant 1
        $assignResponse1 = $this->post(route('markets.stalls.assign', $stall), [
            'taxpayer_id' => $this->commercant1->id,
            'activity' => 'Vente de vêtements',
            'start_date' => '2026-01-01',
            'agreed_fee' => 20000,
            'authorization_ref' => 'AUT-001',
        ]);

        $assignResponse1->assertRedirect();
        $stall->refresh();

        $this->assertTrue($stall->isOccupied());
        $this->assertEquals($this->commercant1->id, $stall->current_occupant_id);
        $this->assertDatabaseHas('market_occupancies', [
            'stall_id' => $stall->id,
            'taxpayer_id' => $this->commercant1->id,
            'status' => 'active',
        ]);

        // 2. Réattribution à Commerçant 2 (changement d'occupant conforme Annexe B)
        $assignResponse2 = $this->post(route('markets.stalls.assign', $stall), [
            'taxpayer_id' => $this->commercant2->id,
            'activity' => 'Boucherie',
            'start_date' => '2026-06-01',
            'agreed_fee' => 22000,
        ]);

        $assignResponse2->assertRedirect();
        $stall->refresh();

        // L'occupant actuel est désormais Commerçant 2
        $this->assertEquals($this->commercant2->id, $stall->current_occupant_id);

        // L'ancienne attribution de Commerçant 1 est automatiquement terminée (pas de double occupation)
        $this->assertDatabaseHas('market_occupancies', [
            'stall_id' => $stall->id,
            'taxpayer_id' => $this->commercant1->id,
            'status' => 'terminated',
        ]);

        // La nouvelle est active
        $this->assertDatabaseHas('market_occupancies', [
            'stall_id' => $stall->id,
            'taxpayer_id' => $this->commercant2->id,
            'status' => 'active',
        ]);
    }

    public function test_release_stall_frees_occupancy(): void
    {
        $this->actingAs($this->admin);

        $market = Market::create(['code' => 'M1', 'name' => 'Marché Test']);
        $stall = MarketStall::create([
            'market_id' => $market->id,
            'code' => 'STALL-01',
            'name' => 'Étal 1',
            'type' => 'etal',
            'base_fee' => 500,
            'status' => 'occupied',
            'current_occupant_id' => $this->commercant1->id,
        ]);

        MarketOccupancy::create([
            'stall_id' => $stall->id,
            'taxpayer_id' => $this->commercant1->id,
            'activity' => 'Légumes',
            'start_date' => '2026-01-01',
            'agreed_fee' => 500,
            'status' => 'active',
        ]);

        $response = $this->post(route('markets.stalls.release', $stall));
        $response->assertRedirect();

        $stall->refresh();
        $this->assertTrue($stall->isAvailable());
        $this->assertNull($stall->current_occupant_id);

        $this->assertDatabaseHas('market_occupancies', [
            'stall_id' => $stall->id,
            'status' => 'terminated',
        ]);
    }

    public function test_receipt_can_be_linked_to_market_stall(): void
    {
        $category = RevenueCategory::create([
            'code' => 'CAT-MARCHE',
            'name' => 'Droits de place',
            'allow_custom_amount' => true,
        ]);

        $market = Market::create(['code' => 'M1', 'name' => 'Marché Test']);
        $stall = MarketStall::create([
            'market_id' => $market->id,
            'code' => 'STALL-01',
            'name' => 'Étal 1',
            'type' => 'etal',
            'base_fee' => 500,
            'status' => 'occupied',
            'current_occupant_id' => $this->commercant1->id,
        ]);

        $service = app(ReceiptService::class);
        $receipt = $service->create([
            'category_id' => $category->id,
            'amount' => 500,
            'payment_method' => 'espece',
            'taxpayer_id' => $this->commercant1->id,
            'market_stall_id' => $stall->id,
            'description' => 'Droit de place étal STALL-01',
        ], $this->regisseur);

        $this->assertNotNull($receipt->market_stall_id);
        $this->assertEquals($stall->id, $receipt->stall->id);
    }
}
