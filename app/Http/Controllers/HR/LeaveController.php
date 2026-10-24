<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $seesAll = $user->hasPermission('hr.manage') || $user->hasPermission('hr.leave.decide');

        $leaves = LeaveRequest::with(['agent.service', 'decider'])
            ->when(! $seesAll, fn ($q) => $q->whereHas('agent', fn ($a) => $a->where('user_id', $user->id)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')->paginate(20)->withQueryString();

        return view('hr.leaves.index', ['leaves' => $leaves, 'seesAll' => $seesAll]);
    }

    public function create(Request $request)
    {
        return view('hr.leaves.create', ['agents' => $this->selectableAgents($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'agent_id' => ['required', Rule::in($this->selectableAgents($request)->keys()->all())],
            'type' => ['required', Rule::in(array_keys(LeaveRequest::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [], ['agent_id' => 'agent', 'start_date' => 'date de début', 'end_date' => 'date de fin', 'type' => 'type']);

        $start = $request->date('start_date');
        $end = $request->date('end_date');

        $overlap = LeaveRequest::where('agent_id', $data['agent_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'Cette période chevauche une autre demande de cet agent.']);
        }

        LeaveRequest::create($data + [
            'days' => (int) $start->diffInDays($end) + 1,
            'created_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->route('hr.leaves.index')->with('success', 'Demande enregistrée, en attente de décision.');
    }

    public function decide(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'decision_note' => ['nullable', 'string', 'max:255', 'required_if:decision,rejected'],
        ], [], ['decision_note' => 'motif']);

        if ($leave->status !== 'pending') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        // Séparation des tâches : on ne décide pas de sa propre demande.
        $leave->loadMissing('agent');
        if ($leave->agent->user_id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas statuer sur votre propre demande.');
        }

        $leave->update([
            'status' => $data['decision'],
            'decision_note' => $data['decision_note'] ?? null,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return back()->with('success', $data['decision'] === 'approved' ? 'Demande approuvée.' : 'Demande refusée.');
    }

    /** @return \Illuminate\Support\Collection<int, string> id => nom */
    private function selectableAgents(Request $request)
    {
        $user = $request->user();
        $query = Agent::where('is_active', true)->orderBy('last_name');

        if (! $user->hasPermission('hr.manage')) {
            $query->where('user_id', $user->id);
        }

        return $query->get()->mapWithKeys(fn (Agent $a) => [$a->id => $a->fullName() . ' (' . $a->matricule . ')']);
    }
}
