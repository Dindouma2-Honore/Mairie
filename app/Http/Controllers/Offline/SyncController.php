<?php

namespace App\Http\Controllers\Offline;

use App\Http\Controllers\Controller;
use App\Models\NumberingBlock;
use App\Services\NumberingService;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /** Jeton CSRF frais : le jeton d'une page mise en cache peut être périmé. */
    public function csrf(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    /** Applique un lot d'opérations saisies hors ligne (idempotent). */
    public function store(Request $request, SyncService $sync): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.uuid' => ['required', 'uuid'],
            'items.*.type' => ['required', 'string', 'max:60'],
            'items.*.payload' => ['required', 'array'],
            'items.*.created_at' => ['nullable', 'date'],
        ]);

        $results = collect($data['items'])
            ->map(fn (array $item) => $sync->apply($item, $request->user()))
            ->values();

        return response()->json(['results' => $results]);
    }

    /** Pages à mettre en cache pour fonctionner sans connexion, selon les droits. */
    public function urls(Request $request): JsonResponse
    {
        $u = $request->user();
        $urls = [route('dashboard', [], false), route('sync.index', [], false)];

        if ($u->hasPermission('revenue.create')) {
            $urls[] = route('revenue.receipts.create', [], false);
        }
        if ($u->hasPermission('revenue.view')) {
            $urls[] = route('revenue.receipts.index', [], false);
        }
        if ($u->hasPermission('civil.birth.create')) {
            $urls[] = route('civil.births.create', [], false);
        }
        if ($u->hasPermission('civil.birth.view')) {
            $urls[] = route('civil.births.index', [], false);
        }
        if ($u->hasPermission('hr.leave.request')) {
            $urls[] = route('hr.leaves.create', [], false);
        }

        return response()->json(['urls' => $urls]);
    }

    /** Réserve un lot de numéros de reçus pour une utilisation hors ligne. */
    public function allocate(Request $request, NumberingService $numbering): JsonResponse
    {
        abort_unless($request->user()->hasPermission('revenue.create'), 403);

        $user = $request->user();
        $year = (int) now()->year;

        // Garde-fou : on ne thésaurise pas des numéros.
        $recent = NumberingBlock::where('key', 'receipt')->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())->count();
        if ($recent >= 3) {
            return response()->json(['message' => 'Trop de lots demandés récemment.'], 429);
        }

        $block = $numbering->allocateBlock('receipt', $year, $user, (int) config('mairie.receipt_block_size'));

        return response()->json([
            'key' => 'receipt',
            'year' => $year,
            'start' => $block->start_number,
            'end' => $block->end_number,
        ]);
    }
}
