<?php

declare(strict_types=1);

namespace Modules\Ecourier\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecourier\Http\Controllers\Concerns\ResolvesCompany;
use Modules\Ecourier\Http\Requests\StoreRecipientRequest;
use Modules\Ecourier\Models\EcourierRecipient;

/**
 * Manages the per-customer Peppol routing details this module owns.
 *
 * A network participant identifier is mandatory for routing and has no home in
 * the host's customer record, so it is kept here and guarded by the host's
 * own customer policy.
 */
class PeppolRecipientController extends Controller
{
    use ResolvesCompany;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        /** @var Collection<int, EcourierRecipient> $recipients */
        $recipients = EcourierRecipient::query()
            ->where('company_id', $this->companyId($request))
            ->with('customer:id,name')
            ->orderBy('customer_id')
            ->get();

        return response()->json([
            'data' => $recipients->map(fn (EcourierRecipient $recipient): array => array_merge(
                $recipient->only($recipient->getFillable()),
                [
                    'id' => $recipient->id,
                    'customer_name' => $recipient->customer?->name,
                ],
            ))->all(),
        ]);
    }

    public function store(StoreRecipientRequest $request): JsonResponse
    {
        $companyId = $this->companyId($request);
        $data = $request->validated();

        $customer = Customer::query()
            ->where('company_id', $companyId)
            ->whereKey((int) $data['customer_id'])
            ->firstOrFail();

        $this->authorize('update', $customer);

        if (isset($data['country'])) {
            $data['country'] = strtoupper((string) $data['country']);
        }

        $recipient = EcourierRecipient::query()->updateOrCreate(
            ['company_id' => $companyId, 'customer_id' => $customer->id],
            $data,
        );

        return response()->json(['data' => $recipient]);
    }

    public function destroy(Request $request, int $customerId): JsonResponse
    {
        $companyId = $this->companyId($request);

        $customer = Customer::query()
            ->where('company_id', $companyId)
            ->whereKey($customerId)
            ->firstOrFail();

        $this->authorize('update', $customer);

        EcourierRecipient::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->delete();

        return response()->json(['deleted' => true]);
    }
}
