<?php

namespace App\Services;

use App\Models\CashClosing;
use App\Models\Receipt;
use App\Models\User;
use App\Support\Audit;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class CashClosingService
{
    public function __construct(private NumberingService $numbering)
    {
    }

    /**
     * Rassemble les reçus valides non clôturés pour préparer la clôture.
     */
    public function prepare(User $collector, ?Carbon $date = null): array
    {
        $closingDate = $date ?? today();

        $query = Receipt::where('collected_by', $collector->id)
            ->where('status', 'paid')
            ->whereNull('cash_closing_id')
            ->whereDate('collected_at', '<=', $closingDate);

        $receipts = (clone $query)->orderBy('collected_at')->get();

        $breakdown = [
            'espece' => (int) (clone $query)->where('payment_method', 'espece')->sum('amount'),
            'mobile_money' => (int) (clone $query)->where('payment_method', 'mobile_money')->sum('amount'),
            'cheque' => (int) (clone $query)->where('payment_method', 'cheque')->sum('amount'),
            'virement' => (int) (clone $query)->where('payment_method', 'virement')->sum('amount'),
        ];

        $totalAmount = array_sum($breakdown);
        $cashExpected = $breakdown['espece'];

        return [
            'date' => $closingDate,
            'receipts' => $receipts,
            'receipts_count' => $receipts->count(),
            'total_amount' => $totalAmount,
            'cash_expected' => $cashExpected,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Effectue la clôture de caisse avec vérification serveur et attribution du numéro CAI-YYYY-NNNNN.
     */
    public function create(User $collector, array $data): CashClosing
    {
        $closingDate = isset($data['closing_date']) ? Carbon::parse($data['closing_date']) : today();
        $prep = $this->prepare($collector, $closingDate);

        if ($prep['receipts_count'] === 0) {
            throw new DomainException('Aucun reçu valide non clôturé à enregistrer pour cette date.');
        }

        $cashCounted = (int) ($data['cash_counted'] ?? 0);
        $difference = $cashCounted - $prep['cash_expected'];

        if ($difference !== 0 && blank($data['notes'] ?? null)) {
            throw new DomainException('Un écart de caisse (' . number_format($difference, 0, ',', ' ') . ' FCFA) doit obligatoirement être expliqué dans les observations.');
        }

        $year = (int) $closingDate->format('Y');

        return DB::transaction(function () use ($collector, $closingDate, $prep, $cashCounted, $difference, $data, $year) {
            $seq = $this->numbering->next('cash_closing', $year);
            $reference = CashClosing::formatReference($year, $seq);

            $closing = CashClosing::create([
                'reference' => $reference,
                'year' => $year,
                'sequence' => $seq,
                'collector_id' => $collector->id,
                'closing_date' => $closingDate,
                'receipts_count' => $prep['receipts_count'],
                'total_amount' => $prep['total_amount'],
                'cash_expected' => $prep['cash_expected'],
                'cash_counted' => $cashCounted,
                'difference' => $difference,
                'breakdown' => $prep['breakdown'],
                'notes' => $data['notes'] ?? null,
                'status' => CashClosing::STATUS_PENDING,
            ]);

            // Rattache tous les reçus à cette clôture (les fige).
            Receipt::whereIn('id', $prep['receipts']->pluck('id'))
                ->update(['cash_closing_id' => $closing->id]);

            Audit::log('cash_closing.created', $closing, null, [
                'reference' => $reference,
                'total' => $prep['total_amount'],
                'diff' => $difference,
            ]);

            return $closing;
        });
    }

    /**
     * Validation de la clôture par le responsable financier ou la direction.
     */
    public function validateClosing(CashClosing $closing, User $reviewer, ?string $notes = null): CashClosing
    {
        if (! $closing->isPending()) {
            throw new DomainException('Cette clôture de caisse a déjà été traitée.');
        }

        if ($closing->collector_id === $reviewer->id) {
            throw new DomainException('Séparation des tâches : vous ne pouvez pas contrôler votre propre clôture de caisse.');
        }

        $closing->update([
            'status' => CashClosing::STATUS_VALIDATED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        Audit::log('cash_closing.validated', $closing, null, [
            'reference' => $closing->reference,
            'reviewer' => $reviewer->name,
        ]);

        return $closing;
    }

    /**
     * Rejet de la clôture : libère les reçus afin qu'ils puissent être corrigés et reclôturés.
     */
    public function rejectClosing(CashClosing $closing, User $reviewer, string $reason): CashClosing
    {
        if (! $closing->isPending()) {
            throw new DomainException('Cette clôture de caisse a déjà été traitée.');
        }

        if ($closing->collector_id === $reviewer->id) {
            throw new DomainException('Séparation des tâches : vous ne pouvez pas contrôler votre propre clôture de caisse.');
        }

        if (blank($reason)) {
            throw new DomainException('Le motif du rejet est obligatoire pour libérer les reçus.');
        }

        return DB::transaction(function () use ($closing, $reviewer, $reason) {
            // Libère les reçus associés
            Receipt::where('cash_closing_id', $closing->id)->update(['cash_closing_id' => null]);

            $closing->update([
                'status' => CashClosing::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $reason,
            ]);

            Audit::log('cash_closing.rejected', $closing, null, [
                'reference' => $closing->reference,
                'reviewer' => $reviewer->name,
                'reason' => $reason,
            ]);

            return $closing;
        });
    }
}
