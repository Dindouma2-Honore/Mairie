<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\MarketOccupancy;
use App\Models\MarketStall;
use App\Models\Taxpayer;
use App\Support\Audit;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StallController extends Controller
{
    public function create(Market $market)
    {
        $stall = new MarketStall([
            'market_id' => $market->id,
            'type' => 'etal',
            'periodicity' => 'journalier',
            'status' => MarketStall::STATUS_AVAILABLE,
        ]);

        return view('markets.stalls.form', compact('market', 'stall'));
    }

    public function store(Request $request, Market $market): RedirectResponse
    {
        $data = $this->validated($request);
        $data['market_id'] = $market->id;

        $stall = MarketStall::create($data);

        Audit::log('market_stall.created', $stall, null, ['code' => $stall->code, 'market' => $market->name]);

        return redirect()->route('markets.stalls.show', $stall)->with('success', 'Emplacement « ' . $stall->code . ' » créé avec succès.');
    }

    public function show(MarketStall $stall)
    {
        $stall->load([
            'market',
            'currentOccupant',
            'tariff',
            'occupancies' => fn ($q) => $q->with(['taxpayer', 'creator'])->latest('start_date'),
            'receipts' => fn ($q) => $q->with('collector')->latest('collected_at'),
        ]);

        $taxpayers = Taxpayer::where('is_active', true)->orderBy('name')->limit(500)->get(['id', 'name', 'phone', 'quarter']);

        return view('markets.stalls.show', compact('stall', 'taxpayers'));
    }

    public function edit(MarketStall $stall)
    {
        $market = $stall->market;

        return view('markets.stalls.form', compact('market', 'stall'));
    }

    public function update(Request $request, MarketStall $stall): RedirectResponse
    {
        $data = $this->validated($request, $stall);

        $stall->update($data);

        Audit::log('market_stall.updated', $stall, null, ['code' => $stall->code]);

        return redirect()->route('markets.stalls.show', $stall)->with('success', 'Emplacement mis à jour.');
    }

    /**
     * Attribue un occupant à l'emplacement.
     * En cas d'emplacement déjà occupé, clôture proprement l'occupation précédente (Annexe B).
     */
    public function assignOccupant(Request $request, MarketStall $stall): RedirectResponse
    {
        $data = $request->validate([
            'taxpayer_id' => ['required', 'exists:taxpayers,id'],
            'activity' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'agreed_fee' => ['required', 'integer', 'min:0'],
            'authorization_ref' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'taxpayer_id' => 'occupant / contribuable',
            'activity' => 'activité exercée',
            'start_date' => 'date de début',
            'agreed_fee' => 'redevance convenue',
        ]);

        DB::transaction(function () use ($stall, $data, $request) {
            // Clôture toute occupation active précédente
            MarketOccupancy::where('stall_id', $stall->id)
                ->where('status', MarketOccupancy::STATUS_ACTIVE)
                ->update([
                    'status' => MarketOccupancy::STATUS_TERMINATED,
                    'end_date' => $data['start_date'],
                ]);

            // Crée la nouvelle attribution
            $occupancy = MarketOccupancy::create([
                'stall_id' => $stall->id,
                'taxpayer_id' => $data['taxpayer_id'],
                'activity' => $data['activity'],
                'start_date' => $data['start_date'],
                'agreed_fee' => $data['agreed_fee'],
                'authorization_ref' => $data['authorization_ref'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => MarketOccupancy::STATUS_ACTIVE,
                'created_by' => $request->user()->id,
            ]);

            // Met à jour l'emplacement
            $stall->update([
                'status' => MarketStall::STATUS_OCCUPIED,
                'current_occupant_id' => $data['taxpayer_id'],
            ]);

            Audit::log('market_stall.assigned', $stall, null, [
                'occupant_id' => $data['taxpayer_id'],
                'agreed_fee' => $data['agreed_fee'],
            ]);
        });

        return back()->with('success', 'Attribution de l’emplacement enregistrée avec succès.');
    }

    /**
     * Libère l'emplacement (fin d'occupation).
     */
    public function releaseStall(Request $request, MarketStall $stall): RedirectResponse
    {
        if ($stall->isAvailable()) {
            return back()->with('info', 'Cet emplacement est déjà disponible.');
        }

        DB::transaction(function () use ($stall) {
            MarketOccupancy::where('stall_id', $stall->id)
                ->where('status', MarketOccupancy::STATUS_ACTIVE)
                ->update([
                    'status' => MarketOccupancy::STATUS_TERMINATED,
                    'end_date' => today(),
                ]);

            $stall->update([
                'status' => MarketStall::STATUS_AVAILABLE,
                'current_occupant_id' => null,
            ]);

            Audit::log('market_stall.released', $stall, null, ['code' => $stall->code]);
        });

        return back()->with('success', 'L’emplacement ' . $stall->code . ' est désormais libéré et disponible.');
    }

    private function validated(Request $request, ?MarketStall $stall = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('market_stalls', 'code')->ignore($stall)],
            'name' => ['required', 'string', 'max:120'],
            'sector' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(MarketStall::TYPES))],
            'dimensions' => ['nullable', 'string', 'max:50'],
            'base_fee' => ['required', 'integer', 'min:0'],
            'periodicity' => ['required', Rule::in(array_keys(MarketStall::PERIODICITIES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'code' => 'numéro / code d’emplacement',
            'name' => 'désignation',
            'base_fee' => 'droit de place de référence',
        ]);
    }
}
