<?php

declare(strict_types=1);

namespace Modules\Ecourier\Peppol;

use RuntimeException;

/**
 * A permanent, operator-fixable problem with the data we were asked to send.
 *
 * These are never worth retrying: the invoice or the module configuration has
 * to change first. The job records the message against the submission so it
 * surfaces in the UI instead of disappearing into a failed queue job.
 */
class PeppolMappingException extends RuntimeException
{
    public static function missingSetting(string $field): self
    {
        return new self("The eCourier setting '{$field}' must be configured before sending.");
    }

    public static function noLines(string $invoiceNumber): self
    {
        return new self("Invoice '{$invoiceNumber}' has no line items to send.");
    }

    public static function noCustomer(string $invoiceNumber): self
    {
        return new self("Invoice '{$invoiceNumber}' has no customer to address the document to.");
    }

    public static function noCurrency(string $invoiceNumber): self
    {
        return new self("Invoice '{$invoiceNumber}' has no currency, so the document amounts have no unit.");
    }

    public static function unsupportedCurrency(string $code): self
    {
        return new self("Currency '{$code}' is not supported by the eCourier API.");
    }

    public static function unregisteredRecipient(int $customerId): self
    {
        return new self(
            "Customer {$customerId} has no electronic invoicing identifier registered. "
            .'Add one under Settings → eCourier → Recipients before sending.'
        );
    }

    public static function incompleteSenderAddress(): self
    {
        return new self(
            'The company profile needs a street, city, postal code and country before '
            .'invoices can be sent: eCourier requires the sender address on every document.'
        );
    }

    public static function unbalancedTotals(string $subtotal, string $tax, string $total): self
    {
        return new self(
            "Invoice totals do not balance: subtotal {$subtotal} + tax {$tax} does not equal total {$total}. "
            .'Document-level discounts cannot be represented in the eCourier JSON totals, '
            .'so the document was not sent rather than sending an invalid one.'
        );
    }

    public static function linesDoNotMatchSubtotal(string $lineSum, string $subtotal): self
    {
        return new self(
            "Invoice line totals sum to {$lineSum} but the invoice subtotal is {$subtotal}. "
            .'Peppol requires the sum of line net amounts to equal the tax-exclusive amount, '
            .'so the document was not sent rather than sending one that would be rejected.'
        );
    }
}
