<?php

namespace App\Http\Controllers\Revenue;

use App\Http\Controllers\Controller;
use App\Models\RevenueCategory;
use App\Models\Taxpayer;
use App\Models\TaxpayerDebt;
use App\Services\NumberingService;
use App\Support\Audit;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecoveryController extends Controller
{
    public function index(Request $request)
    {
        $query = TaxpayerDebt::with(['taxpayer', 'category', 'creator'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('reference', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhereHas('taxpayer', fn ($t) => $t->where('name', 'like', $term)->orWhere('phone', 'like', $term)));
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('quarter'), fn ($q) => $q->whereHas('taxpayer', fn ($t) => $t->where('quarter', $request->string('quarter'))))
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status');
                if ($status == 'overdue') {
                    $q->where('balance', '>', 0)->whereDate('due_date', '<', today())->where('status', '!=', TaxpayerDebt::STATUS_CANCELLED);
                } else {
                    $q->where('status', $status);
                }
            });

        $debts = (clone $query)->latest('due_date')->latest('id')->paginate(20)->withQueryString();

        $activeDebts = TaxpayerDebt::where('status', '!=', TaxpayerDebt::STATUS_CANCELLED);
        $totalDue = (int) (clone $activeDebts)->sum('amount_due');
        $totalPaid = (int) (clone $activeDebts)->sum('amount_paid');
        $totalBalance = (int) (clone $activeDebts)->sum('balance');
        $recoveryRate = $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 1) : 0;
        $overdueCount = TaxpayerDebt::where('balance', '>', 0)->whereDate('due_date', '<', today())->count();

        $categories = RevenueCategory::orderBy('name')->get();
        $quarters = Taxpayer::whereNotNull('quarter')->distinct()->pluck('quarter')->sort()->values();
        $taxpayers = Taxpayer::where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name', 'quarter']);

        return view('revenue.recovery.index', compact(
            'debts', 'totalDue', 'totalPaid', 'totalBalance', 'recoveryRate',
            'overdueCount', 'categories', 'quarters', 'taxpayers'
        ));
    }

    public function store(Request $request, NumberingService $numbering): RedirectResponse
    {
        $data = $request->validate([
            'taxpayer_id' => ['required', 'exists:taxpayers,id'],
            'category_id' => ['required', 'exists:revenue_categories,id'],
            'title' => ['required', 'string', 'max:180'],
            'period' => ['required', 'string', 'max:50'],
            'amount_due' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'taxpayer_id' => 'contribuable',
            'category_id' => 'catégorie',
            'title' => 'libellé',
            'period' => 'période',
            'amount_due' => 'montant dû',
            'due_date' => 'date d’échéance',
        ]);

        $year = (int) Carbon::parse($data['due_date'])->format('Y');

        $debt = DB::transaction(function () use ($data, $year, $numbering, $request) {
            $seq = $numbering->next('taxpayer_debt', $year);
            $reference = TaxpayerDebt::formatReference($year, $seq);

            return TaxpayerDebt::create([
                'reference' => $reference,
                'taxpayer_id' => $data['taxpayer_id'],
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'period' => $data['period'],
                'amount_due' => $data['amount_due'],
                'amount_paid' => 0,
                'balance' => $data['amount_due'],
                'due_date' => $data['due_date'],
                'status' => TaxpayerDebt::STATUS_UNPAID,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
        });

        Audit::log('taxpayer_debt.created', $debt, null, [
            'reference' => $debt->reference,
            'amount_due' => $debt->amount_due,
            'taxpayer_id' => $debt->taxpayer_id,
        ]);

        return back()->with('success', 'Avis de mise en recouvrement « ' . $debt->reference . ' » créé avec succès.');
    }

    public function printNotice(TaxpayerDebt $debt)
    {
        $debt->load(['taxpayer', 'category', 'creator']);

        return view('revenue.recovery.print_notice', compact('debt'));
    }
}
