<?php

declare(strict_types=1);

namespace Modules\Ecourier\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecourier\Http\Controllers\Concerns\ResolvesCompany;
use Modules\Ecourier\Http\Requests\UpdateSettingsRequest;
use Modules\Ecourier\Settings\SettingOptions;

/**
 * Reads and writes the module's per-company settings.
 *
 * Authorized with the host's own `manage company` gate, the same one its
 * built-in company settings screens use. Only keys declared in the module
 * config are ever written, so a crafted request cannot reach unrelated host
 * settings.
 */
class PeppolSettingController extends Controller
{
    use ResolvesCompany;

    public function show(Request $request): JsonResponse
    {
        $company = $this->company($request);
        $this->authorize('manage company', $company);

        return response()->json([
            'settings' => CompanySetting::getSettings(config('ecourier.settings', []), $company->id),
            'options' => SettingOptions::all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $company = $this->company($request);
        $this->authorize('manage company', $company);

        $allowed = config('ecourier.settings', []);
        $settings = [];

        foreach ($request->validated() as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $settings[$key] = $this->normalize($value);
            }
        }

        if ($settings !== []) {
            CompanySetting::setSettings($settings, $company->id);
        }

        return response()->json(['success' => true]);
    }

    private function company(Request $request): Company
    {
        return Company::query()->findOrFail($this->companyId($request));
    }

    /** CompanySetting stores strings, so booleans round-trip as '1' and '0'. */
    private function normalize(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) ($value ?? '');
    }
}
