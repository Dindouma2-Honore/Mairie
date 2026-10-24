<?php

namespace App\Http\Controllers\Revenue;

use App\Http\Controllers\Controller;
use App\Models\CashClosing;
use App\Models\User;
use App\Services\CashClosingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CashClosingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canAudit = $user->hasPermission('revenue.closing.audit');

        $query = CashClosing::with(['collector', 'reviewer'])
            ->when(! $canAudit, fn ($q) => $q->where('collector_id', $user->id))
            ->when($request->filled('collector') && $canAudit, fn ($q) => $q->where('collector_id', $request->integer('collector')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('closing_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('closing_date', '<=', $request->date('to')));

        $closings = $query->latest('closing_date')->latest('id')->paginate(15)->withQueryString();

        $collectors = $canAudit ? User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['regisseur', 'resp_financier']))->get() : collect();

        $stats = [
            'pending_count' => (clone $query)->where('status', CashClosing::STATUS_PENDING)->count(),
            'total_amount' => (clone $query)->where('status', CashClosing::STATUS_VALIDATED)->sum('total_amount'),
            'total_difference' => (clone $query)->where('status', CashClosing::STATUS_VALIDATED)->sum('difference'),
        ];

        return view('revenue.closings.index', compact('closings', 'collectors', 'canAudit', 'stats'));
    }

    public function create(Request $request, CashClosingService $service)
    {
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : today();
        $prep = $service->prepare($request->user(), $date);

        return view('revenue.closings.create', array_merge($prep, [
            'date' => $date,
        ]));
    }

    public function store(Request $request, CashClosingService $service): RedirectResponse
    {
        $data = $request->validate([
            'closing_date' => ['required', 'date', 'before_or_equal:today'],
            'cash_counted' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'cash_counted' => 'espèces comptées',
            'closing_date' => 'date de clôture',
            'notes' => 'observations',
        ]);

        try {
            $closing = $service->create($request->user(), $data);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('revenue.closings.show', $closing)
            ->with('success', 'Clôture de caisse « ' . $closing->reference . ' » enregistrée avec succès. Elle est transmise pour contrôle.');
    }

    public function show(CashClosing $closing)
    {
        $this->authorizeView($closing);

        $closing->load(['collector', 'reviewer', 'receipts' => fn ($q) => $q->with('category')->latest('collected_at')]);

        return view('revenue.closings.show', compact('closing'));
    }

    public function print(CashClosing $closing)
    {
        $this->authorizeView($closing);

        $closing->load(['collector', 'reviewer', 'receipts.category']);

        return view('revenue.closings.print', compact('closing'));
    }

    public function validateClosing(Request $request, CashClosing $closing, CashClosingService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('revenue.closing.audit'), 403, 'Action non autorisée.');

        $data = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->validateClosing($closing, $request->user(), $data['review_notes'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Clôture de caisse ' . $closing->reference . ' validée avec succès.');
    }

    public function reject(Request $request, CashClosing $closing, CashClosingService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('revenue.closing.audit'), 403, 'Action non autorisée.');

        $data = $request->validate([
            'review_notes' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['review_notes' => 'motif du rejet']);

        try {
            $service->rejectClosing($closing, $request->user(), $data['review_notes']);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Clôture rejetée. Les reçus associés ont été libérés pour correction.');
    }

    private function authorizeView(CashClosing $closing): void
    {
        $user = auth()->user();
        if ($user->hasPermission('revenue.closing.audit')) {
            return;
        }

        if ($closing->collector_id === $user->id) {
            return;
        }

        abort(403, 'Accès non autorisé à cette clôture de caisse.');
    }
}
