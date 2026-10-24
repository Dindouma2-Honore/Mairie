<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Service;
use App\Services\NumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $agents = Agent::with('service')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where(fn ($w) => $w->where('last_name', 'like', $term)->orWhere('first_names', 'like', $term)->orWhere('matricule', 'like', $term));
            })
            ->when($request->filled('service'), fn ($q) => $q->where('service_id', $request->integer('service')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('last_name')->paginate(20)->withQueryString();

        return view('hr.agents.index', ['agents' => $agents, 'services' => Service::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('hr.agents.form', ['agent' => new Agent(['status' => 'contractuel', 'is_active' => true]), 'services' => Service::where('is_active', true)->orderBy('name')->pluck('name', 'id')]);
    }

    public function store(Request $request, NumberingService $numbering): RedirectResponse
    {
        $data = $this->validated($request);

        // La règle de matricule définitive est à valider avec la mairie : numérotation automatique par défaut.
        if (blank($data['matricule'] ?? null)) {
            $data['matricule'] = 'AG-' . str_pad((string) $numbering->next('agent'), 5, '0', STR_PAD_LEFT);
        }

        $agent = Agent::create($data);

        return redirect()->route('hr.agents.show', $agent)->with('success', 'Agent enregistré sous le matricule ' . $agent->matricule . '.');
    }

    public function show(Agent $agent)
    {
        $agent->load(['service', 'leaves' => fn ($q) => $q->latest('start_date')->limit(10)]);

        return view('hr.agents.show', compact('agent'));
    }

    public function edit(Agent $agent)
    {
        return view('hr.agents.form', ['agent' => $agent, 'services' => Service::where('is_active', true)->orderBy('name')->pluck('name', 'id')]);
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $agent->update($this->validated($request, $agent));

        return redirect()->route('hr.agents.show', $agent)->with('success', 'Dossier mis à jour.');
    }

    private function validated(Request $request, ?Agent $agent = null): array
    {
        $data = $request->validate([
            'matricule' => ['nullable', 'string', 'max:30', Rule::unique('agents', 'matricule')->ignore($agent)],
            'last_name' => ['required', 'string', 'max:120'],
            'first_names' => ['required', 'string', 'max:160'],
            'sex' => ['nullable', Rule::in(['M', 'F'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'service_id' => ['nullable', 'exists:services,id'],
            'position' => ['nullable', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(array_keys(Agent::STATUSES))],
            'hire_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
        ], [], [
            'last_name' => 'nom', 'first_names' => 'prénoms', 'birth_date' => 'date de naissance',
            'hire_date' => 'date d’embauche', 'end_date' => 'date de fin', 'service_id' => 'service',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
