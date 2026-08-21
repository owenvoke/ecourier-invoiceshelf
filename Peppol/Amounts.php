<?php

declare(strict_types=1);

namespace Modules\Ecourier\Peppol;

/**
 * Numeric formatting for the eCourier JSON payload, which takes every amount
 * as a decimal string.
 *
 * InvoiceShelf stores money as integer minor units — `invoices.total` and
 * `invoice_items.price` are unsigned bigints — so conversion uses integer
 * arithmetic rather than floats to stay exact on large invoices. The scale
 * comes from the invoice currency's own `precision`, so zero-decimal
 * currencies such as JPY are handled correctly.
 */
class Amounts
{
    public const DEFAULT_PRECISION = 2;

    /** Convert integer minor units into a decimal string at the given scale. */
    public static function fromMinorUnits(int $minorUnits, int $precision = self::DEFAULT_PRECISION): string
    {
        $precision = max(0, $precision);
        $sign = $minorUnits < 0 ? '-' : '';
        $absolute = abs($minorUnits);

        if ($precision === 0) {
            return $sign.$absolute;
        }

        $divisor = 10 ** $precision;

        return $sign.intdiv($absolute, $divisor).'.'
            .str_pad((string) ($absolute % $divisor), $precision, '0', STR_PAD_LEFT);
    }

    /** Normalize a host decimal (quantities, tax percentages) to a fixed scale. */
    public static function decimal(mixed $value, int $scale = self::DEFAULT_PRECISION): string
    {
        if (! is_numeric($value)) {
            return number_format(0, $scale, '.', '');
        }

        return number_format((float) $value, $scale, '.', '');
    }

    /** Coerce a host money attribute into integer minor units. */
    public static function toMinorUnits(mixed $value): int
    {
        return is_numeric($value) ? (int) round((float) $value) : 0;
    }
}
