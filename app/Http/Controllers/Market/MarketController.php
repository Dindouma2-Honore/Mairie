<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketController extends Controller
{
    public function index(Request $request)
    {
        $markets = Market::query()
            ->withCount([
                'stalls',
                'stalls as occupied_stalls_count' => fn ($q) => $q->where('status', 'occupied'),
                'stalls as available_stalls_count' => fn ($q) => $q->where('status', 'available'),
            ])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where('name', 'like', $term)->orWhere('code', 'like', $term)->orWhere('location', 'like', $term);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_markets' => Market::where('is_active', true)->count(),
            'total_stalls' => \App\Models\MarketStall::count(),
            'total_occupied' => \App\Models\MarketStall::where('status', 'occupied')->count(),
            'total_available' => \App\Models\MarketStall::where('status', 'available')->count(),
        ];

        return view('markets.index', compact('markets', 'stats'));
    }

    public function create()
    {
        return view('markets.form', ['market' => new Market(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $market = Market::create($data);

        Audit::log('market.created', $market, null, ['code' => $market->code, 'name' => $market->name]);

        return redirect()->route('markets.show', $market)->with('success', 'Marché « ' . $market->name . ' » créé avec succès.');
    }

    public function show(Request $request, Market $market)
    {
        $stallsQuery = $market->stalls()->with(['currentOccupant', 'tariff'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('sector', 'like', $term)
                    ->orWhereHas('currentOccupant', fn ($o) => $o->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('sector'), fn ($q) => $q->where('sector', $request->string('sector')));

        $stalls = (clone $stallsQuery)->orderBy('code')->paginate(25)->withQueryString();

        $sectors = $market->stalls()->whereNotNull('sector')->distinct()->pluck('sector')->sort()->values();

        return view('markets.show', compact('market', 'stalls', 'sectors'));
    }

    public function edit(Market $market)
    {
        return view('markets.form', compact('market'));
    }

    public function update(Request $request, Market $market): RedirectResponse
    {
        $data = $this->validated($request, $market);

        $market->update($data);

        Audit::log('market.updated', $market, null, ['name' => $market->name]);

        return redirect()->route('markets.show', $market)->with('success', 'Marché mis à jour.');
    }

    private function validated(Request $request, ?Market $market = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('markets', 'code')->ignore($market)],
            'name' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'manager_name' => ['nullable', 'string', 'max:150'],
            'manager_phone' => ['nullable', 'string', 'max:30'],
            'sectors_list' => ['nullable', 'string', 'max:500'], // Saisie séparée par des virgules
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [], [
            'code' => 'code du marché',
            'name' => 'nom du marché',
            'location' => 'localisation / quartier',
        ]);

        if (! empty($data['sectors_list'])) {
            $data['sectors'] = array_values(array_filter(array_map('trim', explode(',', $data['sectors_list']))));
        } else {
            $data['sectors'] = [];
        }
        unset($data['sectors_list']);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
