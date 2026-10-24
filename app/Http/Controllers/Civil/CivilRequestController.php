<?php

namespace App\Http\Controllers\Civil;

use App\Http\Controllers\Controller;
use App\Models\BirthAct;
use App\Models\CivilRequest;
use App\Models\DeathAct;
use App\Models\MarriageAct;
use App\Models\Receipt;
use App\Services\NumberingService;
use App\Support\Audit;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CivilRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = CivilRequest::with(['birthAct', 'marriageAct', 'deathAct', 'receipt', 'processor'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('reference', 'like', $term)
                    ->orWhere('requester_name', 'like', $term)
                    ->orWhere('person_concerned', 'like', $term)
                    ->orWhere('requester_phone', 'like', $term));
            })
            ->when($request->filled('act_type'), fn ($q) => $q->where('act_type', $request->string('act_type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => CivilRequest::where('status', CivilRequest::STATUS_PENDING)->count(),
            'processing' => CivilRequest::where('status', CivilRequest::STATUS_PROCESSING)->count(),
            'ready' => CivilRequest::where('status', CivilRequest::STATUS_READY)->count(),
            'delivered_this_month' => CivilRequest::where('status', CivilRequest::STATUS_DELIVERED)->whereBetween('delivered_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        return view('civil.requests.index', compact('requests', 'stats'));
    }

    public function create(Request $request)
    {
        $civilRequest = new CivilRequest([
            'act_type' => $request->query('act_type', 'birth'),
            'document_type' => 'extrait',
            'copies_count' => 1,
            'request_date' => today(),
            'status' => CivilRequest::STATUS_PENDING,
        ]);

        return view('civil.requests.form', compact('civilRequest'));
    }

    public function store(Request $request, NumberingService $numbering): RedirectResponse
    {
        $data = $request->validate([
            'act_type' => ['required', Rule::in(['birth', 'marriage', 'death'])],
            'document_type' => ['required', Rule::in(['extrait', 'copie_integrale'])],
            'copies_count' => ['required', 'integer', 'min:1', 'max:20'],
            'person_concerned' => ['required', 'string', 'max:160'],
            'external_act_ref' => ['nullable', 'string', 'max:100'],
            'requester_name' => ['required', 'string', 'max:160'],
            'requester_phone' => ['nullable', 'string', 'max:30'],
            'requester_id_number' => ['nullable', 'string', 'max:60'],
            'amount' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'person_concerned' => 'personne concernée',
            'requester_name' => 'nom du demandeur',
            'copies_count' => 'nombre d’exemplaires',
        ]);

        $year = (int) now()->year;

        $civilRequest = DB::transaction(function () use ($data, $year, $numbering) {
            $seq = $numbering->next('civil_request', $year);
            $reference = CivilRequest::formatReference($year, $seq);

            return CivilRequest::create($data + [
                'reference' => $reference,
                'request_date' => today(),
                'status' => CivilRequest::STATUS_PENDING,
            ]);
        });

        Audit::log('civil_request.created', $civilRequest, null, ['reference' => $civilRequest->reference]);

        return redirect()->route('civil.requests.show', $civilRequest)
            ->with('success', 'Demande « ' . $civilRequest->reference . ' » enregistrée au guichet.');
    }

    public function show(CivilRequest $request)
    {
        $request->load(['birthAct', 'marriageAct', 'deathAct', 'receipt', 'processor']);

        return view('civil.requests.show', ['civilRequest' => $request]);
    }

    public function updateStatus(Request $req, CivilRequest $civilRequest): RedirectResponse
    {
        $data = $req->validate([
            'status' => ['required', Rule::in(array_keys(CivilRequest::STATUSES))],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $updates = [
            'status' => $data['status'],
            'processed_by' => auth()->id(),
        ];

        if ($data['notes']) {
            $updates['notes'] = $data['notes'];
        }

        if ($data['status'] === CivilRequest::STATUS_READY && ! $civilRequest->ready_at) {
            $updates['ready_at'] = now();
        }

        if ($data['status'] === CivilRequest::STATUS_DELIVERED && ! $civilRequest->delivered_at) {
            $updates['delivered_at'] = now();
        }

        $civilRequest->update($updates);

        Audit::log('civil_request.status_changed', $civilRequest, null, ['status' => $data['status']]);

        return back()->with('success', 'Statut de la demande mis à jour : ' . $civilRequest->statusLabel() . '.');
    }
}
