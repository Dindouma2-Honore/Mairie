<?php

namespace App\Http\Controllers\Civil;

use App\Http\Controllers\Controller;
use App\Models\CivilCentre;
use App\Models\DeathAct;
use App\Services\DeathActService;
use App\Support\Audit;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeathActController extends Controller
{
    public function index(Request $request)
    {
        $acts = DeathAct::with(['centre', 'creator', 'validator'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('deceased_last_name', 'like', $term)
                    ->orWhere('deceased_first_names', 'like', $term)
                    ->orWhere('declarant_name', 'like', $term)
                    ->orWhere('verification_code', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('centre'), fn ($q) => $q->where('centre_id', $request->integer('centre')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $centres = CivilCentre::orderBy('name')->get();

        return view('civil.deaths.index', compact('acts', 'centres'));
    }

    public function create()
    {
        return view('civil.deaths.form', [
            'act' => new DeathAct([
                'death_date' => today(),
                'declaration_date' => today(),
                'death_place' => 'Moundou',
                'sex' => 'M',
                'marital_status' => 'celibataire',
            ]),
            'centres' => CivilCentre::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, DeathActService $service): RedirectResponse
    {
        $data = $service->validate($request->all());
        $act = $service->createDraft($data, $request->user());

        Audit::log('death_act.created', $act, null, ['deceased' => $act->deceasedLabel()]);

        return redirect()->route('civil.deaths.show', $act)
            ->with('success', 'Déclaration de décès enregistrée sous forme de brouillon.');
    }

    public function show(DeathAct $death)
    {
        $death->load(['centre', 'creator', 'validator']);

        return view('civil.deaths.show', ['act' => $death]);
    }

    public function edit(DeathAct $death)
    {
        abort_unless($death->isDraft(), 403, 'Un acte validé ne peut plus être modifié.');

        return view('civil.deaths.form', [
            'act' => $death,
            'centres' => CivilCentre::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, DeathAct $death, DeathActService $service): RedirectResponse
    {
        $data = $service->validate($request->all());

        try {
            $service->updateDraft($death, $data);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        Audit::log('death_act.updated', $death, null, ['deceased' => $death->deceasedLabel()]);

        return redirect()->route('civil.deaths.show', $death)->with('success', 'Brouillon mis à jour.');
    }

    public function validateAct(Request $request, DeathAct $death, DeathActService $service): RedirectResponse
    {
        try {
            $service->validateAct($death, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        Audit::log('death_act.validated', $death, null, ['registry' => $death->registryLabel()]);

        return back()->with('success', 'Acte de décès validé — numéro de registre ' . $death->registryLabel() . '.');
    }

    public function print(DeathAct $death)
    {
        abort_unless($death->isValidated(), 403, 'Seul un acte de décès validé peut être imprimé officiellement.');

        $death->load(['centre', 'validator']);

        return view('civil.templates.death', ['act' => $death]);
    }
}
