<?php

namespace App\Http\Controllers\Civil;

use App\Http\Controllers\Controller;
use App\Models\ActDelivery;
use App\Models\BirthAct;
use App\Models\CivilCentre;
use App\Models\Receipt;
use App\Services\ActRenderer;
use App\Services\BirthActService;
use App\Support\Audit;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BirthActController extends Controller
{
    public function index(Request $request)
    {
        $acts = BirthAct::with(['centre', 'creator'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('child_last_name', 'like', $term)
                    ->orWhere('child_first_names', 'like', $term)
                    ->orWhere('mother_name', 'like', $term)
                    ->orWhere('father_name', 'like', $term)
                    ->orWhere('verification_code', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('centre'), fn ($q) => $q->where('centre_id', $request->integer('centre')))
            ->latest('id')->paginate(20)->withQueryString();

        return view('civil.births.index', ['acts' => $acts, 'centres' => CivilCentre::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('civil.births.form', [
            'act' => new BirthAct(['declaration_date' => today(), 'birth_date_precision' => 'exact']),
            'centres' => CivilCentre::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request, BirthActService $service): RedirectResponse
    {
        $data = $service->validate($request->all());
        $act = $service->createDraft($data, $request->user());

        return redirect()->route('civil.births.show', $act)->with('success', 'Brouillon enregistré. Il doit être validé par un officier d’état civil.');
    }

    public function show(BirthAct $birth)
    {
        $birth->load(['centre', 'creator', 'validator', 'deliveries.deliverer']);

        return view('civil.births.show', ['act' => $birth]);
    }

    public function edit(BirthAct $birth)
    {
        abort_unless($birth->isDraft(), 403, 'Un acte validé ne peut plus être modifié.');

        return view('civil.births.form', [
            'act' => $birth,
            'centres' => CivilCentre::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, BirthAct $birth, BirthActService $service): RedirectResponse
    {
        $data = $service->validate($request->all());

        try {
            $service->updateDraft($birth, $data);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('civil.births.show', $birth)->with('success', 'Brouillon mis à jour.');
    }

    public function validateAct(Request $request, BirthAct $birth, BirthActService $service): RedirectResponse
    {
        try {
            $service->validateAct($birth, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        Audit::log('birth_act.validated', $birth, null, ['registry' => $birth->registryLabel()]);

        return back()->with('success', 'Acte validé — numéro de registre ' . $birth->registryLabel() . '.');
    }

    /** Enregistre la délivrance (traçabilité) puis ouvre la page d'impression. */
    public function deliver(Request $request, BirthAct $birth): RedirectResponse
    {
        abort_unless($birth->isValidated(), 403, 'Seul un acte validé peut être délivré.');

        $data = $request->validate([
            'copy_type' => ['required', Rule::in(array_keys(ActDelivery::TYPES))],
            'requester_name' => ['nullable', 'string', 'max:160'],
            'receipt_number' => ['nullable', 'string', 'max:30'],
        ], [], ['copy_type' => 'type de copie', 'receipt_number' => 'numéro de reçu']);

        $receiptId = null;
        if (filled($data['receipt_number'] ?? null)) {
            $receipt = Receipt::where('number', trim($data['receipt_number']))->where('status', 'paid')->first();
            if (! $receipt) {
                return back()->withInput()->with('error', 'Reçu introuvable ou annulé : ' . $data['receipt_number']);
            }
            $receiptId = $receipt->id;
        }

        $delivery = $birth->deliveries()->create([
            'copy_type' => $data['copy_type'],
            'requester_name' => $data['requester_name'] ?? null,
            'receipt_id' => $receiptId,
            'delivered_by' => $request->user()->id,
        ]);

        return redirect()->route('civil.deliveries.print', $delivery);
    }

    public function print(ActDelivery $delivery, ActRenderer $renderer)
    {
        $act = $delivery->birthAct;
        abort_unless($act->isValidated(), 403);

        $delivery->increment('print_count');
        Audit::log('birth_act.printed', $act, null, ['delivery' => $delivery->id, 'copy' => $delivery->copy_type]);

        return view('civil.births.print', [
            'html' => $renderer->render($act, $delivery),
            'act' => $act,
            'delivery' => $delivery,
        ]);
    }
}
