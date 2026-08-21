<?php

declare(strict_types=1);

namespace Modules\Ecourier\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Resolves the acting company from the `company` header that the host's
 * CompanyMiddleware guarantees for an authenticated company user.
 */
trait ResolvesCompany
{
    protected function companyId(Request $request): int
    {
        $companyId = (int) $request->header('company');

        abort_if($companyId <= 0, 400, 'A company context is required.');

        return $companyId;
    }
}
