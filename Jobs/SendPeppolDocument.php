<?php

declare(strict_types=1);

namespace Modules\Ecourier\Jobs;

use App\Models\Invoice;
use Ecourier\Exceptions\AuthenticationException;
use Ecourier\Exceptions\EcourierException;
use Ecourier\Exceptions\ValidationException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Ecourier\Models\EcourierSubmission;
use Modules\Ecourier\Peppol\InvoiceDocumentFactory;
use Modules\Ecourier\Peppol\PeppolMappingException;
use Modules\Ecourier\Settings\ModuleSettings;
use Modules\Ecourier\Support\ConnectorFactory;
use Throwable;

/**
 * Submits one invoice to eCourier as a Peppol document.
 *
 * Queued so a slow network call never blocks the request that triggered it,
 * and so transport failures can be retried without the operator re-sending by
 * hand. Permanent problems — bad mapping, rejected payload, bad credentials —
 * are recorded and swallowed, because retrying them would only fail again.
 */
class SendPeppolDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $backoff = [30, 120];

    /**
     * The document UUID is generated here rather than in handle(), so it is
     * fixed when the job is serialised: every retry of this dispatch reuses it
     * and eCourier can discard a duplicate, while a fresh send — including a
     * forced re-send — gets a new one.
     */
    public string $documentUuid;

    public function __construct(
        public Invoice $invoice,
        public bool $force = false,
    ) {
        $this->documentUuid = (string) Str::uuid();
    }

    public function handle(
        ModuleSettings $settings,
        InvoiceDocumentFactory $documents,
        ConnectorFactory $connectors,
    ): void {
        $companyId = (int) $this->invoice->company_id;

        if (! $settings->enabled($companyId)) {
            return;
        }

        if (! $this->force && EcourierSubmission::hasSuccessfulSubmission($companyId, (int) $this->invoice->id)) {
            return;
        }

        $channel = $settings->channel($companyId);

        $submission = EcourierSubmission::query()->create([
            'company_id' => $companyId,
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'channel' => $channel->value,
            'status' => EcourierSubmission::STATUS_PENDING,
            'document_uuid' => $this->documentUuid,
        ]);

        try {
            $document = $documents->build($this->invoice, $this->documentUuid);
            $result = $connectors->for($companyId)->documents()->sendJson($channel, $document);
        } catch (AuthenticationException|PeppolMappingException|ValidationException $exception) {
            // Permanent: the invoice, the payload, or the credentials must
            // change before this can succeed. Record it and stop.
            $submission->markFailed($this->describe($exception));

            return;
        } catch (Throwable $exception) {
            $submission->markFailed($this->describe($exception));

            throw $exception;
        }

        $submission->markSent($result->id, $result->e2eMessageUuid);

        Log::info('Sent invoice to eCourier.', [
            'company_id' => $companyId,
            'invoice_number' => $this->invoice->invoice_number,
            'channel' => $channel->value,
            'document_id' => $result->id,
        ]);
    }

    private function describe(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            $errors = json_encode($exception->getErrors(), JSON_UNESCAPED_SLASHES);

            return $exception->getMessage().' '.($errors === false ? '' : $errors);
        }

        // These two carry messages written for whoever has to fix the problem,
        // so they are recorded verbatim. Anything else is unexpected, and the
        // class name is worth keeping for diagnosis.
        if ($exception instanceof EcourierException || $exception instanceof PeppolMappingException) {
            return $exception->getMessage();
        }

        return $exception::class.': '.$exception->getMessage();
    }
}
