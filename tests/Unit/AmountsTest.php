<?php

declare(strict_types=1);

use Modules\Ecourier\Peppol\Amounts;

/*
 * Amount conversion is the send-path logic that needs no host application, and
 * the piece most worth pinning down: InvoiceShelf stores money as integer minor
 * units, while every eCourier amount is a decimal string at the currency's own
 * precision.
 */

it('formats minor units at the currency precision', function (int $minorUnits, int $precision, string $expected): void {
    expect(Amounts::fromMinorUnits($minorUnits, $precision))->toBe($expected);
})->with([
    'zero' => [0, 2, '0.00'],
    'cents only' => [7, 2, '0.07'],
    'ten cents' => [70, 2, '0.70'],
    'whole unit' => [100, 2, '1.00'],
    'typical amount' => [125000, 2, '1250.00'],
    'negative credit' => [-4250, 2, '-42.50'],
    'large amount stays exact' => [922337203685477, 2, '9223372036854.77'],
    'zero-decimal currency (JPY)' => [1250, 0, '1250'],
    'three-decimal currency (BHD)' => [1250, 3, '1.250'],
]);

it('defaults to two decimal places', function (): void {
    expect(Amounts::fromMinorUnits(1234))->toBe('12.34')
        ->and(Amounts::DEFAULT_PRECISION)->toBe(2);
});

it('never renders a negative scale', function (): void {
    expect(Amounts::fromMinorUnits(1234, -1))->toBe('1234');
});

it('normalizes host decimals', function (mixed $value, string $expected): void {
    expect(Amounts::decimal($value))->toBe($expected);
})->with([
    'decimal string' => ['2.00', '2.00'],
    'float' => [1.5, '1.50'],
    'integer string' => ['25', '25.00'],
    'null' => [null, '0.00'],
    'non-numeric' => ['not a number', '0.00'],
]);

it('coerces host money attributes to minor units', function (mixed $value, int $expected): void {
    expect(Amounts::toMinorUnits($value))->toBe($expected);
})->with([
    'integer' => [1250, 1250],
    'numeric string' => ['1250', 1250],
    'rounds a float' => [1250.6, 1251],
    'null' => [null, 0],
]);
