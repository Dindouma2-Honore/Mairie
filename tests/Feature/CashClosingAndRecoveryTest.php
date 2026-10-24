<?php

namespace Tests\Feature;

use App\Models\CashClosing;
use App\Models\Receipt;
use App\Models\RevenueCategory;
use App\Models\Role;
use App\Models\Tariff;
use App\Models\Taxpayer;
use App\Models\TaxpayerDebt;
use App\Models\User;
use App\Services\CashClosingService;
use App\Services\ReceiptService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashClosingAndRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private User $regisseur;
    private User $financier;
    private RevenueCategory $category;
    private Tariff $tariff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->regisseur = User::create([
            'name' => 'Jean Regisseur',
            'email' => 'jean.regisseur@test.local',
            'password' => 'secret123',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $this->regisseur->roles()->attach(Role::where('slug', 'regisseur')->first());

        $this->financier = User::create([
            'name' => 'Paul Financier',
            'email' => 'paul.financier@test.local',
            'password' => 'secret123',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $this->financier->roles()->attach(Role::where('slug', 'resp_financier')->first());

        $this->category = RevenueCategory::create([
            'code' => 'TEST-MARCHE',
            'name' => 'Droits de place',
            'allow_custom_amount' => false,
            'is_active' => true,
        ]);

        $this->tariff = Tariff::create([
            'category_id' => $this->category->id,
            'label' => 'Place journalière',
            'amount' => 500,
            'effective_from' => now()->startOfYear(),
            'is_active' => true,
        ]);
    }

    public function test_cash_closing_requires_receipts(): void
    {
        $service = app(CashClosingService::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Aucun reçu valide');

        $service->create($this->regisseur, [
            'closing_date' => today()->toDateString(),
            'cash_counted' => 0,
        ]);
    }

    public function test_cash_closing_full_workflow_and_separation_of_duties(): void
    {
        $receiptService = app(ReceiptService::class);
        $closingService = app(CashClosingService::class);

        // 1. Émission de deux reçus par le régisseur
        $r1 = $receiptService->create([
            'category_id' => $this->category->id,
            'tariff_id' => $this->tariff->id,
            'quantity' => 2,
            'payment_method' => 'espece',
        ], $this->regisseur);

        $r2 = $receiptService->create([
            'category_id' => $this->category->id,
            'tariff_id' => $this->tariff->id,
            'quantity' => 1,
            'payment_method' => 'mobile_money',
            'transaction_ref' => 'AIRTEL-12345',
        ], $this->regisseur);

        $this->assertEquals(1000, $r1->amount);
        $this->assertEquals(500, $r2->amount);

        // 2. Création de la clôture par le régisseur
        $closing = $closingService->create($this->regisseur, [
            'closing_date' => today()->toDateString(),
            'cash_counted' => 1000, // exact montant en espèces
            'notes' => 'Clôture de journée normale',
        ]);

        $this->assertEquals(1500, $closing->total_amount);
        $this->assertEquals(1000, $closing->cash_expected);
        $this->assertEquals(1000, $closing->cash_counted);
        $this->assertEquals(0, $closing->difference);
        $this->assertStringStartsWith('CAI-', $closing->reference);

        // Vérification du rattachement des reçus
        $r1->refresh();
        $this->assertEquals($closing->id, $r1->cash_closing_id);
        $this->assertTrue($r1->isClosed());

        // 3. Un reçu inclus dans une clôture NE PEUT PLUS être annulé (Règle section 5.14)
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('fait partie d’une clôture de caisse et ne peut plus être annulé');
        $receiptService->cancel($r1, $this->regisseur, 'Erreur de saisie');
    }

    public function test_separation_of_duties_prevents_self_audit(): void
    {
        $receiptService = app(ReceiptService::class);
        $closingService = app(CashClosingService::class);

        $receiptService->create([
            'category_id' => $this->category->id,
            'tariff_id' => $this->tariff->id,
            'quantity' => 1,
            'payment_method' => 'espece',
        ], $this->regisseur);

        $closing = $closingService->create($this->regisseur, [
            'closing_date' => today()->toDateString(),
            'cash_counted' => 500,
        ]);

        // Le régisseur ne peut PAS contrôler sa propre clôture
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Séparation des tâches');
        $closingService->validateClosing($closing, $this->regisseur, 'Tentative auto-visa');
    }

    public function test_rejection_frees_receipts_for_correction(): void
    {
        $receiptService = app(ReceiptService::class);
        $closingService = app(CashClosingService::class);

        $r = $receiptService->create([
            'category_id' => $this->category->id,
            'tariff_id' => $this->tariff->id,
            'quantity' => 1,
            'payment_method' => 'espece',
        ], $this->regisseur);

        $closing = $closingService->create($this->regisseur, [
            'closing_date' => today()->toDateString(),
            'cash_counted' => 300,
            'notes' => 'Manque 200 FCFA',
        ]);

        $r->refresh();
        $this->assertEquals($closing->id, $r->cash_closing_id);

        // Le financier rejette avec motif
        $closingService->rejectClosing($closing, $this->financier, 'Écart injustifié non accepté');

        $closing->refresh();
        $this->assertTrue($closing->isRejected());
        $this->assertEquals('Écart injustifié non accepté', $closing->review_notes);

        // Reçus libérés pour correction
        $r->refresh();
        $this->assertNull($r->cash_closing_id);
    }

    public function test_taxpayer_show_and_recovery_flow(): void
    {
        $taxpayer = Taxpayer::create([
            'reference' => 'CONT-001',
            'type' => 'entreprise',
            'name' => 'Quincaillerie du Sud',
            'phone' => '+235 66 00 11 22',
            'quarter' => 'Dombao',
        ]);

        $debt = TaxpayerDebt::create([
            'reference' => 'AVIS-2026-00001',
            'taxpayer_id' => $taxpayer->id,
            'category_id' => $this->category->id,
            'title' => 'Droit de place annuel',
            'period' => '2026',
            'amount_due' => 25000,
            'amount_paid' => 5000,
            'balance' => 20000,
            'due_date' => now()->addDays(20),
            'status' => 'partial',
        ]);

        $this->actingAs($this->financier);

        // Page contribuable
        $response = $this->get(route('revenue.taxpayers.show', $taxpayer));
        $response->assertStatus(200);
        $response->assertSee('Quincaillerie du Sud');
        $response->assertSee(\App\Support\Money::fcfa(20000));

        // Page recouvrement
        $recovResponse = $this->get(route('revenue.recovery.index'));
        $recovResponse->assertStatus(200);
        $recovResponse->assertSee('AVIS-2026-00001');

        // Impression avis de mise en recouvrement
        $noticeResponse = $this->get(route('revenue.recovery.print_notice', $debt));
        $noticeResponse->assertStatus(200);
        $noticeResponse->assertSee('Avis de Mise en Recouvrement');
    }
}
