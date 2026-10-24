<?php

namespace App\Http\Controllers\Revenue;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\RevenueCategory;
use App\Models\Taxpayer;
use App\Services\ReceiptService;
use App\Support\Audit;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Receipt::with(['category', 'collector', 'taxpayer'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('number', 'like', $term)
                    ->orWhere('payer_name', 'like', $term)
                    ->orWhereHas('taxpayer', fn ($t) => $t->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('collected_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('collected_at', '<=', $request->date('to')));

        $total = (clone $query)->where('status', 'paid')->sum('amount');

        return view('revenue.receipts.index', [
            'receipts' => $query->latest('collected_at')->latest('id')->paginate(20)->withQueryString(),
            'categories' => RevenueCategory::orderBy('name')->get(),
            'total' => $total,
        ]);
    }

    public function create()
    {
        $categories = RevenueCategory::where('is_active', true)->orderBy('name')->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'custom' => $c->allow_custom_amount,
                'tariffs' => $c->tariffsOn(now())->map(fn ($t) => ['id' => $t->id, 'label' => $t->label, 'amount' => $t->amount])->all(),
            ]);

        return view('revenue.receipts.create', [
            'categories' => $categories,
            'taxpayers' => Taxpayer::where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name', 'quarter']),
        ]);
    }

    /** Chemin classique (sans JavaScript). Le chemin hors ligne passe par /synchronisation. */
    public function store(Request $request, ReceiptService $service): RedirectResponse
    {
        $data = $service->validate($request->all());

        try {
            $receipt = $service->create($data, $request->user());
        } catch (DomainException $e) {
            throw ValidationException::withMessages(['tariff_id' => $e->getMessage()]);
        }

        return redirect()->route('revenue.receipts.show', $receipt)->with('success', 'Reçu ' . $receipt->number . ' enregistré.');
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['category', 'tariff', 'taxpayer', 'collector', 'canceller']);

        return view('revenue.receipts.show', compact('receipt'));
    }

    public function print(Receipt $receipt)
    {
        $receipt->load(['category', 'taxpayer', 'collector']);

        return view('revenue.receipts.print', compact('receipt'));
    }

    public function cancel(Request $request, Receipt $receipt, ReceiptService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']], [], ['reason' => 'motif']);

        try {
            $service->cancel($receipt, $request->user(), $data['reason']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        Audit::log('receipt.cancelled', $receipt, null, ['reason' => $data['reason']]);

        return back()->with('success', 'Reçu annulé. Il reste conservé dans le journal.');
    }
}
