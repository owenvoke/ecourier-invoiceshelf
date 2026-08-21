<?php

declare(strict_types=1);

namespace Modules\Ecourier\Observers;

use App\Models\Invoice;
use Modules\Ecourier\Jobs\SendPeppolDocument;
use Modules\Ecourier\Settings\ModuleSettings;

/**
 * Opt-in auto-send: dispatches a Peppol submission when an invoice is marked
 * as sent.
 *
 * InvoiceShelf publishes no domain event for this — `Invoice::send()` simply
 * sets `status = SENT` and saves — so the module observes the model instead.
 */
class InvoiceObserver
{
    public function __construct(private ModuleSettings $settings) {}

    public function updated(Invoice $invoice): void
    {
        if (! $invoice->wasChanged('status') || $invoice->status !== Invoice::STATUS_SENT) {
            return;
        }

        $companyId = (int) $invoice->company_id;

        if (! $this->settings->enabled($companyId) || ! $this->settings->autoSend($companyId)) {
            return;
        }

        SendPeppolDocument::dispatch($invoice);
    }
}
