<?php

declare(strict_types=1);

namespace Modules\Ecourier\Http\Requests;

use Ecourier\Enums\AccountSchemeId;
use Ecourier\Enums\Channel;
use Ecourier\Enums\IdentifierScheme;
use Ecourier\Enums\PaymentMeansCode;
use Ecourier\Enums\TaxCategoryCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ecourier_enabled' => ['nullable', 'boolean'],
            'ecourier_auto_send' => ['nullable', 'boolean'],
            'ecourier_api_key' => ['nullable', 'string', 'max:255'],
            'ecourier_channel' => ['nullable', Rule::in(self::values(Channel::class))],
            'ecourier_sender_scheme' => ['nullable', Rule::in(self::values(IdentifierScheme::class))],
            'ecourier_sender_id' => ['nullable', 'string', 'max:255'],
            'ecourier_sender_vat_id' => ['nullable', 'string', 'max:255'],
            'ecourier_sender_registration_number' => ['nullable', 'string', 'max:255'],
            'ecourier_tax_category' => ['nullable', Rule::in(self::values(TaxCategoryCode::class))],
            'ecourier_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ecourier_unit_code' => ['nullable', 'string', 'max:16'],
            'ecourier_payment_means_code' => ['nullable', Rule::in(self::values(PaymentMeansCode::class))],
            'ecourier_account_scheme' => ['nullable', Rule::in(self::values(AccountSchemeId::class))],
            'ecourier_account_id' => ['nullable', 'string', 'max:255'],
            'ecourier_bank_id' => ['nullable', 'string', 'max:255'],
            'ecourier_bank_name' => ['nullable', 'string', 'max:255'],
            'ecourier_payment_terms_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The sender scheme is typed by hand — see StoreRecipientRequest.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('ecourier_sender_scheme') && is_string($this->input('ecourier_sender_scheme'))) {
            $this->merge([
                'ecourier_sender_scheme' => strtoupper(trim($this->input('ecourier_sender_scheme'))),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ecourier_sender_scheme.in' => 'The sender identifier scheme must be one eCourier supports, for example DK:CVR.',
        ];
    }

    /** @return array<int, string> */
    private static function values(string $enum): array
    {
        return array_column($enum::cases(), 'value');
    }
}
