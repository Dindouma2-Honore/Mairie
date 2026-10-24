<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\RevenueCategory;
use App\Models\Tariff;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ReceiptService
{
    public function __construct(private NumberingService $numbering)
    {
    }

    public static function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('revenue_categories', 'id')->where('is_active', true)],
            'tariff_id' => ['nullable', 'integer', 'exists:tariffs,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'amount' => ['nullable', 'integer', 'min:1', 'max:1000000000'],
            'taxpayer_id' => ['nullable', 'integer', 'exists:taxpayers,id'],
            'market_stall_id' => ['nullable', 'integer', 'exists:market_stalls,id'],
            'payer_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(array_keys(config('mairie.payment_methods')))],
            'transaction_ref' => ['nullable', 'string', 'max:80', 'required_if:payment_method,mobile_money,cheque,virement'],
            'collected_at' => ['nullable', 'date'],
            // Réservés à la synchronisation hors ligne
            'number_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'number_seq' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function validate(array $payload): array
    {
        return Validator::make($payload, self::rules(), [], [
            'category_id' => 'catégorie',
            'tariff_id' => 'tarif',
            'payment_method' => 'mode de paiement',
            'transaction_ref' => 'référence de transaction',
            'quantity' => 'quantité',
            'amount' => 'montant',
        ])->validate();
    }

    /**
     * Crée un reçu. Le montant est toujours recalculé côté serveur à partir du tarif :
     * le client (surtout hors ligne) ne décide jamais du prix.
     */
    public function create(array $data, User $user, ?string $clientUuid = null, bool $offline = false): Receipt
    {
        $collectedAt = $this->resolveCollectedAt($data['collected_at'] ?? null, $offline);
        $category = RevenueCategory::findOrFail($data['category_id']);
        $quantity = (int) ($data['quantity'] ?? 1);

        [$tariff, $unit] = $this->resolveUnitAmount($category, $data, $collectedAt);

        return DB::transaction(function () use ($data, $user, $clientUuid, $offline, $collectedAt, $category, $tariff, $unit, $quantity) {
            [$year, $seq] = $this->resolveNumber($data, $user, $offline, $collectedAt);

            return Receipt::create([
                'number' => Receipt::formatNumber($year, $seq),
                'number_year' => $year,
                'number_seq' => $seq,
                'client_uuid' => $clientUuid,
                'category_id' => $category->id,
                'tariff_id' => $tariff?->id,
                'taxpayer_id' => $data['taxpayer_id'] ?? null,
                'market_stall_id' => $data['market_stall_id'] ?? null,
                'payer_name' => $data['payer_name'] ?? null,
                'description' => $data['description'] ?? null,
                'quantity' => $quantity,
                'unit_amount' => $unit,
                'amount' => $unit * $quantity,
                'payment_method' => $data['payment_method'],
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'status' => 'paid',
                'collected_by' => $user->id,
                'collected_at' => $collectedAt,
                'created_offline' => $offline,
            ]);
        });
    }

    public function cancel(Receipt $receipt, User $user, string $reason): Receipt
    {
        if ($receipt->isCancelled()) {
            throw new DomainException('Ce reçu est déjà annulé.');
        }

        if ($receipt->isClosed()) {
            throw new DomainException('Ce reçu fait partie d’une clôture de caisse et ne peut plus être annulé.');
        }

        $receipt->update([
            'status' => 'cancelled',
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);

        return $receipt;
    }

    private function resolveCollectedAt(?string $value, bool $offline): Carbon
    {
        if (! $offline || ! $value) {
            return now();
        }

        // Date saisie hors ligne : acceptée, mais jamais dans le futur.
        $date = Carbon::parse($value);

        return $date->isFuture() ? now() : $date;
    }

    /** @return array{0: ?Tariff, 1: int} */
    private function resolveUnitAmount(RevenueCategory $category, array $data, Carbon $at): array
    {
        if (! empty($data['tariff_id'])) {
            $tariff = Tariff::findOrFail($data['tariff_id']);

            if ($tariff->category_id !== $category->id) {
                throw new DomainException('Ce tarif n’appartient pas à la catégorie choisie.');
            }
            if (! $tariff->isValidOn($at)) {
                throw new DomainException('Ce tarif n’était pas en vigueur à la date de l’encaissement.');
            }

            return [$tariff, $tariff->amount];
        }

        if ($category->allow_custom_amount && ! empty($data['amount'])) {
            return [null, (int) $data['amount']];
        }

        throw new DomainException('Choisissez un tarif (cette catégorie n’accepte pas de montant libre).');
    }

    /** @return array{0: int, 1: int} [année, numéro] */
    private function resolveNumber(array $data, User $user, bool $offline, Carbon $collectedAt): array
    {
        if ($offline && ! empty($data['number_seq']) && ! empty($data['number_year'])) {
            $year = (int) $data['number_year'];
            $seq = (int) $data['number_seq'];

            if (! $this->numbering->userOwnsNumber('receipt', $year, $user->id, $seq)) {
                throw new DomainException('Numéro de reçu non attribué à cet utilisateur.');
            }
            if (Receipt::where('number_year', $year)->where('number_seq', $seq)->exists()) {
                throw new DomainException('Ce numéro de reçu a déjà été utilisé.');
            }

            return [$year, $seq];
        }

        $year = (int) $collectedAt->year;

        return [$year, $this->numbering->next('receipt', $year)];
    }
}
