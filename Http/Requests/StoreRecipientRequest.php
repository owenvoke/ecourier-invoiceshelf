<?php

declare(strict_types=1);

namespace Modules\Ecourier\Http\Requests;

use Ecourier\Enums\IdentifierScheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipientRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'min:1'],
            'scheme' => ['required', 'string', Rule::in(array_column(IdentifierScheme::cases(), 'value'))],
            'identifier' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'vat_id' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'size:2'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The scheme is typed by hand, so accept any casing and stray whitespace.
     * eCourier's participant scheme is a closed enum, so the value itself is
     * still validated against it rather than being passed through.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('scheme') && is_string($this->input('scheme'))) {
            $this->merge(['scheme' => strtoupper(trim($this->input('scheme')))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheme.in' => 'The identifier scheme must be one eCourier supports, for example DK:CVR.',
        ];
    }
}
