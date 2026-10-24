<?php

namespace App\Services;

use App\Models\SyncReceipt;
use App\Models\User;
use DomainException;
use Illuminate\Validation\ValidationException;

/**
 * Applique côté serveur les opérations saisies hors ligne.
 * Idempotent : un même client_uuid n'est jamais appliqué deux fois.
 */
class SyncService
{
    /** type => [permission requise, méthode] */
    private const HANDLERS = [
        'receipt.create' => ['revenue.create', 'receiptCreate'],
        'birth_act.draft' => ['civil.birth.create', 'birthDraft'],
    ];

    public function __construct(
        private ReceiptService $receipts,
        private BirthActService $births,
    ) {
    }

    /**
     * @param array{uuid: string, type: string, payload: array, created_at?: string} $item
     * @return array{uuid: string, status: string, message?: string, errors?: array, url?: string, label?: string}
     */
    public function apply(array $item, User $user): array
    {
        $uuid = $item['uuid'];

        if ($done = SyncReceipt::where('client_uuid', $uuid)->first()) {
            return ['uuid' => $uuid, 'status' => 'duplicate'] + $done->result;
        }

        $handler = self::HANDLERS[$item['type']] ?? null;
        if (! $handler) {
            return ['uuid' => $uuid, 'status' => 'rejected', 'message' => 'Type d’opération inconnu.'];
        }
        [$permission, $method] = $handler;

        if (! $user->hasPermission($permission)) {
            return ['uuid' => $uuid, 'status' => 'rejected', 'message' => 'Droit insuffisant pour cette opération.'];
        }

        try {
            $payload = $item['payload'];
            if (! empty($item['created_at']) && empty($payload['collected_at'])) {
                $payload['collected_at'] = $item['created_at'];
            }

            $result = $this->{$method}($payload, $user, $uuid);
        } catch (ValidationException $e) {
            return [
                'uuid' => $uuid,
                'status' => 'rejected',
                'message' => 'Données invalides : ' . collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ];
        } catch (DomainException $e) {
            return ['uuid' => $uuid, 'status' => 'rejected', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            // Erreur technique : l'opération n'est pas consommée, le client réessaiera.
            report($e);

            return ['uuid' => $uuid, 'status' => 'error', 'message' => 'Erreur serveur, nouvelle tentative plus tard.'];
        }

        SyncReceipt::create(['client_uuid' => $uuid, 'user_id' => $user->id, 'type' => $item['type'], 'result' => $result]);

        return ['uuid' => $uuid, 'status' => 'ok'] + $result;
    }

    private function receiptCreate(array $payload, User $user, string $uuid): array
    {
        $offline = ! empty($payload['number_seq']);
        $data = $this->receipts->validate($payload);
        $receipt = $this->receipts->create($data, $user, $uuid, $offline);

        return [
            'url' => route('revenue.receipts.show', $receipt),
            'label' => $receipt->number,
            'message' => 'Reçu ' . $receipt->number . ' enregistré.',
        ];
    }

    private function birthDraft(array $payload, User $user, string $uuid): array
    {
        $data = $this->births->validate($payload);
        $act = $this->births->createDraft($data, $user, $uuid, offline: ! empty($payload['offline']));

        return [
            'url' => route('civil.births.show', $act),
            'label' => $act->fullName(),
            'message' => 'Brouillon d’acte enregistré pour ' . $act->fullName() . '.',
        ];
    }
}
