<?php

declare(strict_types=1);

namespace Modules\Ecourier\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail of every submission attempt.
 *
 * Documents cannot be recalled once they reach the network, so this is also
 * the idempotency guard: a successful submission blocks further sends for the
 * same invoice unless the operator explicitly forces one.
 *
 * @property int $id
 * @property int $company_id
 * @property int|null $invoice_id
 * @property string $invoice_number
 * @property string $channel
 * @property string|null $document_uuid
 * @property string $status
 * @property string|null $document_id
 * @property string|null $e2e_message_uuid
 * @property string|null $error
 */
class EcourierSubmission extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $table = 'ecourier_submissions';

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'invoice_id',
        'invoice_number',
        'channel',
        'status',
        'document_uuid',
        'document_id',
        'e2e_message_uuid',
        'error',
    ];

    public function markSent(string $documentId, string|null $e2eMessageUuid): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'document_id' => $documentId,
            'e2e_message_uuid' => $e2eMessageUuid,
            'error' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error' => mb_substr($error, 0, 2000),
        ]);
    }

    public static function hasSuccessfulSubmission(int $companyId, int $invoiceId): bool
    {
        return self::query()
            ->where('company_id', $companyId)
            ->where('invoice_id', $invoiceId)
            ->where('status', self::STATUS_SENT)
            ->exists();
    }
}
