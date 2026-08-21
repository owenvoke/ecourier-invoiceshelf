<?php

declare(strict_types=1);

namespace Modules\Ecourier\Peppol;

use App\Models\Address;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Ecourier\Data\Invoice\InvoiceContactData;
use Ecourier\Data\Invoice\InvoiceDocumentData;
use Ecourier\Data\Invoice\InvoiceLineData;
use Ecourier\Data\Invoice\InvoicePartyData;
use Ecourier\Data\Invoice\InvoicePaymentAccountData;
use Ecourier\Data\Invoice\InvoicePaymentData;
use Ecourier\Data\Invoice\InvoicePaymentMeansData;
use Ecourier\Data\Invoice\InvoiceTaxCategoryData;
use Ecourier\Data\Invoice\InvoiceTotalsData;
use Ecourier\Data\Invoice\ParticipantIdentifier;
use Ecourier\Data\Invoice\SimplifiedAddressData;
use Ecourier\Enums\Currency;
use Ecourier\Enums\DocumentType;
use Ecourier\Enums\IdentifierScheme;
use Illuminate\Support\Collection;
use Modules\Ecourier\Models\EcourierRecipient;
use Modules\Ecourier\Settings\ModuleSettings;

/**
 * Builds an eCourier `InvoiceDocumentData` payload from a host invoice.
 *
 * A 2.x module can read the host's models directly, so almost everything the
 * document needs comes from the invoice itself: its own currency (including
 * that currency's decimal precision), the customer's billing address and ISO
 * country code, and per-line taxes when the invoice uses them.
 *
 * Only two things are not in the host's data model and come from configuration
 * instead: the company's own Peppol participant identifier, from module
 * settings, and each customer's, from the recipient registry.
 */
class InvoiceDocumentFactory
{
    public function __construct(private ModuleSettings $settings) {}

    /**
     * @param  string|null  $documentUuid  Idempotency key echoed to eCourier so a
     *                                     retried submission is not delivered twice.
     */
    public function build(Invoice $invoice, string|null $documentUuid = null): InvoiceDocumentData
    {
        $invoice->loadMissing([
            'items.taxes',
            'taxes',
            'currency',
            'customer.billingAddress.country',
            'company.address.country',
        ]);

        $invoiceNumber = $invoice->invoice_number;
        $companyId = (int) $invoice->company_id;

        $customer = $invoice->customer;
        $currency = $invoice->currency;

        if ($customer === null) {
            throw PeppolMappingException::noCustomer($invoiceNumber);
        }

        if ($currency === null) {
            throw PeppolMappingException::noCurrency($invoiceNumber);
        }

        $items = $invoice->items;

        if ($items->isEmpty()) {
            throw PeppolMappingException::noLines($invoiceNumber);
        }

        $documentCurrency = Currency::tryFrom($currency->code)
            ?? throw PeppolMappingException::unsupportedCurrency($currency->code);

        $precision = $currency->precision;

        $subTotal = Amounts::toMinorUnits($invoice->sub_total);
        $tax = Amounts::toMinorUnits($invoice->tax);
        $total = Amounts::toMinorUnits($invoice->total);

        $this->assertTotalsBalance($subTotal, $tax, $total, $items, $precision);

        return new InvoiceDocumentData(
            type: DocumentType::Invoice,
            id: $invoiceNumber,
            issueDate: $this->date($invoice->invoice_date),
            currency: $documentCurrency,
            supplier: $this->supplier($invoice->company, $companyId),
            customer: $this->customer($customer, $companyId),
            lines: $this->lines($invoice, $companyId, $precision),
            totals: new InvoiceTotalsData(
                subtotalAmount: Amounts::fromMinorUnits($subTotal, $precision),
                taxAmount: Amounts::fromMinorUnits($tax, $precision),
                totalAmount: Amounts::fromMinorUnits($total, $precision),
            ),
            uuid: $documentUuid,
            dueDate: $invoice->due_date ? $this->date($invoice->due_date) : null,
            orderReference: $this->text($invoice->reference_number),
            payment: $this->payment($companyId, $invoiceNumber),
        );
    }

    /**
     * Refuse to send a document whose arithmetic Peppol would reject.
     *
     * InvoiceShelf computes `total = sub_total - discount_val + tax`, and the
     * eCourier JSON totals object has no allowance/charge field, so a
     * discounted invoice cannot be represented faithfully. Failing here keeps
     * an invalid document off the network.
     *
     * @param  Collection<int, InvoiceItem>  $items
     */
    private function assertTotalsBalance(int $subTotal, int $tax, int $total, $items, int $precision): void
    {
        if ($subTotal + $tax !== $total) {
            throw PeppolMappingException::unbalancedTotals(
                Amounts::fromMinorUnits($subTotal, $precision),
                Amounts::fromMinorUnits($tax, $precision),
                Amounts::fromMinorUnits($total, $precision),
            );
        }

        $lineSum = $items->sum(fn (InvoiceItem $item): int => Amounts::toMinorUnits($item->total));

        if ($lineSum !== $subTotal) {
            throw PeppolMappingException::linesDoNotMatchSubtotal(
                Amounts::fromMinorUnits($lineSum, $precision),
                Amounts::fromMinorUnits($subTotal, $precision),
            );
        }
    }

    private function supplier(Company|null $company, int $companyId): InvoicePartyData
    {
        $scheme = $this->settings->senderScheme($companyId)
            ?? throw PeppolMappingException::missingSetting('sender_scheme');
        $identifier = $this->settings->senderId($companyId)
            ?? throw PeppolMappingException::missingSetting('sender_id');

        return new InvoicePartyData(
            participant: new ParticipantIdentifier($scheme, $identifier),
            name: $this->text($company?->name),
            registrationNumber: $this->settings->senderRegistrationNumber($companyId),
            vatId: $this->settings->senderVatId($companyId),
            simplifiedAddress: $this->address($company?->address)
                ?? throw PeppolMappingException::incompleteSenderAddress(),
            contact: $this->contact(
                $this->text($company?->name),
                null,
                $this->text($company?->address?->phone),
            ),
        );
    }

    private function customer(Customer $customer, int $companyId): InvoicePartyData
    {
        $recipient = EcourierRecipient::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->first();

        if ($recipient === null) {
            throw PeppolMappingException::unregisteredRecipient($customer->id);
        }

        $scheme = IdentifierScheme::tryFrom($recipient->scheme)
            ?? throw PeppolMappingException::unregisteredRecipient($customer->id);

        return new InvoicePartyData(
            participant: new ParticipantIdentifier($scheme, $recipient->identifier),
            name: $this->text($recipient->name) ?? $this->text($customer->name),
            registrationNumber: $this->text($recipient->registration_number),
            vatId: $this->text($recipient->vat_id),
            simplifiedAddress: $this->recipientAddress($recipient, $customer->billingAddress),
            contact: $this->contact(
                $this->text($customer->contact_name) ?? $this->text($customer->name),
                $this->text($customer->email),
                $this->text($customer->phone),
            ),
        );
    }

    /** @return array<int, InvoiceLineData> */
    private function lines(Invoice $invoice, int $companyId, int $precision): array
    {
        $fallback = $this->fallbackTaxCategory($invoice, $companyId);
        $unitCode = $this->settings->unitCode($companyId);
        $lines = [];

        foreach ($invoice->items->values() as $index => $item) {
            $lines[] = new InvoiceLineData(
                id: (string) ($index + 1),
                name: $this->text($item->name),
                description: $this->text($item->description),
                quantity: Amounts::decimal($item->quantity),
                unitCode: $this->text($item->unit_name) ?? $unitCode,
                unitPrice: Amounts::fromMinorUnits(Amounts::toMinorUnits($item->price), $precision),
                lineTotal: Amounts::fromMinorUnits(Amounts::toMinorUnits($item->total), $precision),
                taxCategory: $this->lineTaxCategory($item, $companyId, $fallback),
                itemId: $item->item_id === null ? null : (string) $item->item_id,
            );
        }

        return $lines;
    }

    /**
     * Prefer the rate on the line itself; fall back to the invoice-level rate
     * and finally to the configured default.
     */
    private function lineTaxCategory(InvoiceItem $item, int $companyId, InvoiceTaxCategoryData $fallback): InvoiceTaxCategoryData
    {
        $tax = $item->taxes->first();

        if ($tax === null) {
            return $fallback;
        }

        return new InvoiceTaxCategoryData(
            code: $this->settings->taxCategory($companyId),
            percent: Amounts::decimal($tax->percent),
        );
    }

    private function fallbackTaxCategory(Invoice $invoice, int $companyId): InvoiceTaxCategoryData
    {
        $tax = $invoice->taxes->count() === 1 ? $invoice->taxes->first() : null;

        $percent = $tax !== null
            ? Amounts::decimal($tax->percent)
            : $this->settings->taxPercent($companyId);

        return new InvoiceTaxCategoryData(
            code: $this->settings->taxCategory($companyId),
            percent: $percent,
        );
    }

    private function payment(int $companyId, string $invoiceNumber): InvoicePaymentData|null
    {
        $code = $this->settings->paymentMeansCode($companyId);
        $termsNote = $this->settings->paymentTermsNote($companyId);

        if ($code === null && $termsNote === null) {
            return null;
        }

        $means = null;

        if ($code !== null) {
            $accountId = $this->settings->accountId($companyId);

            $means = [new InvoicePaymentMeansData(
                code: $code,
                remittanceText: $invoiceNumber,
                account: $accountId === null ? null : new InvoicePaymentAccountData(
                    id: $accountId,
                    scheme: $this->settings->accountScheme($companyId),
                    bankId: $this->settings->bankId($companyId),
                    bankName: $this->settings->bankName($companyId),
                ),
            )];
        }

        return new InvoicePaymentData(paymentMeans: $means, paymentTermsNote: $termsNote);
    }

    /**
     * Registry overrides win, so a Peppol-specific address can be recorded
     * without touching the customer's billing address.
     */
    private function recipientAddress(EcourierRecipient $recipient, Address|null $billing): SimplifiedAddressData|null
    {
        $street = $this->text($recipient->street) ?? $this->text($billing?->address_street_1);
        $city = $this->text($recipient->city) ?? $this->text($billing?->city);
        $postalCode = $this->text($recipient->postal_code) ?? $this->text($billing?->zip);
        $country = $this->text($recipient->country) ?? $this->text($billing?->country?->code);

        return $this->simplifiedAddress($street, $city, $postalCode, $country);
    }

    private function address(Address|null $address): SimplifiedAddressData|null
    {
        return $this->simplifiedAddress(
            $this->text($address?->address_street_1),
            $this->text($address?->city),
            $this->text($address?->zip),
            $this->text($address?->country?->code),
        );
    }

    private function simplifiedAddress(string|null $street, string|null $city, string|null $postalCode, string|null $country): SimplifiedAddressData|null
    {
        if ($street === null || $city === null || $postalCode === null || $country === null) {
            return null;
        }

        return new SimplifiedAddressData(
            streetName: $street,
            city: $city,
            postalCode: $postalCode,
            country: strtoupper($country),
        );
    }

    private function contact(string|null $name, string|null $email, string|null $phone): InvoiceContactData|null
    {
        if ($email === null && $phone === null) {
            return null;
        }

        return new InvoiceContactData(name: $name, email: $email, phone: $phone);
    }

    private function date(\DateTimeInterface|string $value): string
    {
        return $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : substr($value, 0, 10);
    }

    private function text(mixed $value): string|null
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
