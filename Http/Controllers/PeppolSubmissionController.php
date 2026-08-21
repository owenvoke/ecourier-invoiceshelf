<?php

declare(strict_types=1);

namespace Modules\Ecourier\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecourier\Http\Controllers\Concerns\ResolvesCompany;
use Modules\Ecourier\Jobs\SendPeppolDocument;
use Modules\Ecourier\Models\EcourierSubmission;
use Modules\Ecourier\Settings\ModuleSettings;

/** Queues Peppol submissions and exposes their history. */
class PeppolSubmissionController extends Controller
{
    use ResolvesCompany;

    public function __construct(private ModuleSettings $settings) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $submissions = EcourierSubmission::query()
            ->where('company_id', $this->companyId($request))
            ->when($request->integer('invoice_id'), fn ($query, $id) => $query->where('invoice_id', $id))
            ->latest('id')
            ->limit(100)
            ->get();

        return response()->json(['data' => $submissions]);
    }

    public function store(Request $request, int $invoiceId): JsonResponse
    {
        $companyId = $this->companyId($request);

        $invoice = Invoice::query()
            ->where('company_id', $companyId)
            ->whereKey($invoiceId)
            ->firstOrFail();

        // The host's own send action checks this same policy.
        $this->authorize('send', $invoice);

        abort_unless($this->settings->enabled($companyId), 409, 'The eCourier module is disabled for this company.');

        $force = $request->boolean('force');

        if (! $force && EcourierSubmission::hasSuccessfulSubmission($companyId, (int) $invoice->id)) {
            return response()->json([
                'queued' => false,
                'message' => 'This invoice has already been sent to eCourier. Pass force to send it again.',
            ], 409);
        }

        SendPeppolDocument::dispatch($invoice, $force);

        return response()->json([
            'queued' => true,
            'message' => 'The invoice has been queued for delivery to eCourier.',
        ], 202);
    }
}
