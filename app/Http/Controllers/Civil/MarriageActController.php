<?php

namespace App\Http\Controllers\Civil;

use App\Http\Controllers\Controller;
use App\Models\CivilCentre;
use App\Models\MarriageAct;
use App\Services\MarriageActService;
use App\Support\Audit;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarriageActController extends Controller
{
    public function index(Request $request)
    {
        $acts = MarriageAct::with(['centre', 'creator', 'validator'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('husband_last_name', 'like', $term)
                    ->orWhere('husband_first_names', 'like', $term)
                    ->orWhere('wife_last_name', 'like', $term)
                    ->orWhere('wife_first_names', 'like', $term)
                    ->orWhere('verification_code', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('centre'), fn ($q) => $q->where('centre_id', $request->integer('centre')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $centres = CivilCentre::orderBy('name')->get();

        return view('civil.marriages.index', compact('acts', 'centres'));
    }

    public function create()
    {
        return view('civil.marriages.form', [
            'act' => new MarriageAct([
                'celebration_date' => today(),
                'celebration_place' => 'Mairie de Moundou',
                'husband_marital_status' => 'celibataire',
                'wife_marital_status' => 'celibataire',
            ]),
            'centres' => CivilCentre::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, MarriageActService $service): RedirectResponse
    {
        $data = $service->validate($request->all());
        $act = $service->createDraft($data, $request->user());

        Audit::log('marriage_act.created', $act, null, ['couple' => $act->coupleLabel()]);

        return redirect()->route('civil.marriages.show', $act)
            ->with('success', 'Brouillon d’acte de mariage enregistré. Il doit être validé par un officier d’état civil.');
    }

    public function show(MarriageAct $marriage)
    {
        $marriage->load(['centre', 'creator', 'validator']);

        return view('civil.marriages.show', ['act' => $marriage]);
    }

    public function edit(MarriageAct $marriage)
    {
        abort_unless($marriage->isDraft(), 403, 'Un acte validé ne peut plus être modifié directement.');

        return view('civil.marriages.form', [
            'act' => $marriage,
            'centres' => CivilCentre::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, MarriageAct $marriage, MarriageActService $service): RedirectResponse
    {
        $data = $service->validate($request->all());

        try {
            $service->updateDraft($marriage, $data);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        Audit::log('marriage_act.updated', $marriage, null, ['couple' => $marriage->coupleLabel()]);

        return redirect()->route('civil.marriages.show', $marriage)->with('success', 'Brouillon mis à jour.');
    }

    public function validateAct(Request $request, MarriageAct $marriage, MarriageActService $service): RedirectResponse
    {
        try {
            $service->validateAct($marriage, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        Audit::log('marriage_act.validated', $marriage, null, ['registry' => $marriage->registryLabel()]);

        return back()->with('success', 'Acte de mariage validé — numéro de registre ' . $marriage->registryLabel() . '.');
    }

    public function print(MarriageAct $marriage)
    {
        abort_unless($marriage->isValidated(), 403, 'Seul un acte de mariage validé peut être imprimé officiellement.');

        $marriage->load(['centre', 'validator']);

        return view('civil.templates.marriage', ['act' => $marriage]);
    }
}
