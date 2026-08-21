<?php

declare(strict_types=1);

namespace Modules\Ecourier\Settings;

use BackedEnum;
use Ecourier\Enums\AccountSchemeId;
use Ecourier\Enums\Channel;
use Ecourier\Enums\IdentifierScheme;
use Ecourier\Enums\PaymentMeansCode;
use Ecourier\Enums\TaxCategoryCode;

/**
 * The option lists behind the settings screen's pickers.
 *
 * Every value comes from the SDK's enums, which mirror the closed enums in the
 * eCourier API spec, so a new scheme or currency shipped by the SDK appears
 * here without another edit. Options are emitted as `{value, label}` objects
 * because the host's select components read `option[labelKey]` and emit
 * `option[valueProp]` — a plain list of strings renders blank.
 */
final class SettingOptions
{
    /**
     * UNCL 5305 VAT category codes. The eCourier spec declares the enum without
     * descriptions, so the standard's own wording is used.
     */
    private const TAX_CATEGORY_LABELS = [
        'S' => 'Standard rate',
        'AA' => 'Lower rate',
        'Z' => 'Zero rated goods',
        'E' => 'Exempt from tax',
        'AE' => 'VAT reverse charge',
        'K' => 'VAT exempt for intra-community supply of goods',
        'G' => 'Free export item, VAT not charged',
        'O' => 'Services outside scope of tax',
        'L' => 'Canary Islands general indirect tax',
        'M' => 'Tax for production, services and imports in Ceuta and Melilla',
    ];

    /** UNCL 4461 payment means, worded as the eCourier API spec describes them. */
    private const PAYMENT_MEANS_LABELS = [
        '30' => 'Credit transfer (use for IBAN transfers)',
        '31' => 'Debit transfer (rare for invoicing)',
        '42' => 'Payment to bank account (recommended for DK BBAN)',
    ];

    /**
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public static function all(): array
    {
        return [
            'channels' => self::from(Channel::class),
            'identifier_schemes' => self::from(IdentifierScheme::class),
            'tax_categories' => self::from(TaxCategoryCode::class, self::TAX_CATEGORY_LABELS),
            'payment_means_codes' => self::from(PaymentMeansCode::class, self::PAYMENT_MEANS_LABELS),
            'account_schemes' => self::from(AccountSchemeId::class),
        ];
    }

    /**
     * @param  class-string<BackedEnum>  $enum
     * @param  array<array-key, string>  $labels  Descriptions for opaque codes.
     *                                            Numeric codes such as '30'
     *                                            arrive as integer keys.
     * @return array<int, array{value: string, label: string}>
     */
    private static function from(string $enum, array $labels = []): array
    {
        return array_map(static function (BackedEnum $case) use ($labels): array {
            $value = (string) $case->value;

            return [
                'value' => $value,
                'label' => isset($labels[$value]) ? $value.' — '.$labels[$value] : $value,
            ];
        }, $enum::cases());
    }
}
