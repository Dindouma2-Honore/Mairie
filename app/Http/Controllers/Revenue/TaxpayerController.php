<?php

namespace App\Http\Controllers\Revenue;

use App\Http\Controllers\Controller;
use App\Models\Taxpayer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxpayerController extends Controller
{
    public function index(Request $request)
    {
        $taxpayers = Taxpayer::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('reference', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->withCount('receipts')
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('revenue.taxpayers.index', compact('taxpayers'));
    }

    public function create()
    {
        return view('revenue.taxpayers.form', ['taxpayer' => new Taxpayer(['type' => 'personne', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $taxpayer = Taxpayer::create($this->validated($request));

        return redirect()->route('revenue.taxpayers.index')->with('success', 'Contribuable « ' . $taxpayer->name . ' » enregistré.');
    }

    public function show(Taxpayer $taxpayer)
    {
        $taxpayer->load([
            'receipts' => fn ($q) => $q->with('category')->latest('collected_at'),
            'debts' => fn ($q) => $q->with('category')->latest('due_date'),
        ]);

        $totalPaid = $taxpayer->totalPaid();
        $totalDue = $taxpayer->totalDue();
        $totalBalance = $taxpayer->totalBalance();

        return view('revenue.taxpayers.show', compact('taxpayer', 'totalPaid', 'totalDue', 'totalBalance'));
    }

    public function edit(Taxpayer $taxpayer)
    {
        return view('revenue.taxpayers.form', compact('taxpayer'));
    }

    public function update(Request $request, Taxpayer $taxpayer): RedirectResponse
    {
        $taxpayer->update($this->validated($request, $taxpayer));

        return redirect()->route('revenue.taxpayers.index')->with('success', 'Contribuable mis à jour.');
    }

    private function validated(Request $request, ?Taxpayer $taxpayer = null): array
    {
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:40', Rule::unique('taxpayers', 'reference')->ignore($taxpayer)],
            'type' => ['required', Rule::in(['personne', 'entreprise'])],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'quarter' => ['nullable', 'string', 'max:150'],
            'activity' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [], ['name' => 'nom', 'quarter' => 'quartier', 'reference' => 'référence']);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
