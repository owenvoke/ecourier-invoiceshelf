<?php

declare(strict_types=1);

namespace Modules\Ecourier\Settings;

use App\Models\CompanySetting;
use Ecourier\Enums\AccountSchemeId;
use Ecourier\Enums\Channel;
use Ecourier\Enums\IdentifierScheme;
use Ecourier\Enums\PaymentMeansCode;
use Ecourier\Enums\TaxCategoryCode;
use Modules\Ecourier\Peppol\Amounts;

/**
 * Typed reader over the host's per-company settings.
 *
 * `CompanySetting` stores every value as a string, so this class turns those
 * strings back into the enums and scalars the eCourier SDK expects and is the
 * only place that has to think about it.
 */
class ModuleSettings
{
    /** Keys are prefixed so they cannot collide with host settings. */
    public const PREFIX = 'ecourier_';

    public function enabled(int $companyId): bool
    {
        return $this->bool($companyId, 'enabled', true);
    }

    public function autoSend(int $companyId): bool
    {
        return $this->bool($companyId, 'auto_send', false);
    }

    public function apiKey(int $companyId): string|null
    {
        return $this->string($companyId, 'api_key');
    }

    public function channel(int $companyId): Channel
    {
        return Channel::tryFrom((string) $this->string($companyId, 'channel')) ?? Channel::Peppol;
    }

    public function senderScheme(int $companyId): IdentifierScheme|null
    {
        return IdentifierScheme::tryFrom((string) $this->string($companyId, 'sender_scheme'));
    }

    public function senderId(int $companyId): string|null
    {
        return $this->string($companyId, 'sender_id');
    }

    public function senderVatId(int $companyId): string|null
    {
        return $this->string($companyId, 'sender_vat_id');
    }

    public function senderRegistrationNumber(int $companyId): string|null
    {
        return $this->string($companyId, 'sender_registration_number');
    }

    public function taxCategory(int $companyId): TaxCategoryCode
    {
        return TaxCategoryCode::tryFrom((string) $this->string($companyId, 'tax_category')) ?? TaxCategoryCode::S;
    }

    /** Fallback VAT rate, used only when an invoice carries no usable tax line. */
    public function taxPercent(int $companyId): string
    {
        return Amounts::decimal($this->string($companyId, 'tax_percent') ?? '0');
    }

    /**
     * Optional: a line's own unit name wins, and when neither is set the
     * payload omits unit_code rather than guessing one.
     */
    public function unitCode(int $companyId): string|null
    {
        return $this->string($companyId, 'unit_code');
    }

    public function paymentMeansCode(int $companyId): PaymentMeansCode|null
    {
        return PaymentMeansCode::tryFrom((string) $this->string($companyId, 'payment_means_code'));
    }

    public function accountScheme(int $companyId): AccountSchemeId
    {
        return AccountSchemeId::tryFrom((string) $this->string($companyId, 'account_scheme')) ?? AccountSchemeId::IBAN;
    }

    public function accountId(int $companyId): string|null
    {
        return $this->string($companyId, 'account_id');
    }

    public function bankId(int $companyId): string|null
    {
        return $this->string($companyId, 'bank_id');
    }

    public function bankName(int $companyId): string|null
    {
        return $this->string($companyId, 'bank_name');
    }

    public function paymentTermsNote(int $companyId): string|null
    {
        return $this->string($companyId, 'payment_terms_note');
    }

    public function string(int $companyId, string $key, string|null $default = null): string|null
    {
        $value = CompanySetting::getSetting(self::PREFIX.$key, $companyId);

        if ($value === null || ! is_scalar($value)) {
            return $default;
        }

        $value = trim((string) $value);

        return $value === '' ? $default : $value;
    }

    public function bool(int $companyId, string $key, bool $default = false): bool
    {
        $value = $this->string($companyId, $key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
