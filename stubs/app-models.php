<?php

declare(strict_types=1);

/**
 * Stubs for the InvoiceShelf classes this module reads.
 *
 * A 2.x module runs inside the host application, so the host is not an
 * installable dependency and its classes cannot be autoloaded during static
 * analysis. These declarations exist only for PHPStan: they are listed under
 * `scanDirectories` and are not part of the Composer autoload map, so they are
 * never loaded at runtime.
 *
 * Only the members this module actually touches are declared. Keep them in
 * step with the host version named in the README.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string|null $address_street_1
 * @property string|null $address_street_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $phone
 * @property Country|null $country
 */
class Address extends Model
{
    public const BILLING_TYPE = 'billing';

    public const SHIPPING_TYPE = 'shipping';

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}

/**
 * @property int $id
 * @property string $code ISO 3166-1 alpha-2
 * @property string $name
 */
class Country extends Model {}

/**
 * @property int $id
 * @property string $code ISO 4217
 * @property string $name
 * @property int $precision Decimal places for this currency
 */
class Currency extends Model {}

/**
 * @property int $id
 * @property string $name
 * @property Address|null $address
 */
class Company extends Model
{
    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }
}

/**
 * @property int $id
 * @property string $option
 * @property string|null $value
 * @property int $company_id
 */
class CompanySetting extends Model
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public static function setSettings($settings, int $company_id): void {}

    /**
     * @param  array<int, string>  $settings
     * @return Collection<string, string|null>
     */
    public static function getSettings($settings, int $company_id): Collection {}

    public static function getSetting(string $key, int $company_id): mixed {}

    /**
     * @return Collection<string, string|null>
     */
    public static function getAllSettings(int $company_id): Collection {}
}

/**
 * @property int $id
 * @property int|null $company_id
 * @property string|null $name
 * @property string|null $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property Address|null $billingAddress
 * @property Address|null $shippingAddress
 */
class Customer extends Model
{
    public function billingAddress(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(Address::class);
    }
}

/**
 * @property int $id
 * @property string $name
 * @property numeric-string $percent
 */
class TaxType extends Model {}

/**
 * @property int $id
 * @property string $name
 * @property numeric-string $percent
 * @property int $amount
 */
class Tax extends Model {}

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property numeric-string $quantity
 * @property string|null $unit_name
 * @property int $price
 * @property int $tax
 * @property int $total
 * @property int|null $item_id
 * @property Collection<int, Tax> $taxes
 */
class InvoiceItem extends Model
{
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }
}

/**
 * @property int $id
 * @property int|null $company_id
 * @property int|null $customer_id
 * @property int|null $currency_id
 * @property string $invoice_number
 * @property string|null $reference_number
 * @property string $status
 * @property string $paid_status
 * @property Carbon|string $invoice_date
 * @property Carbon|string|null $due_date
 * @property int $sub_total
 * @property int $tax
 * @property int $total
 * @property int|null $discount_val
 * @property Collection<int, InvoiceItem> $items
 * @property Collection<int, Tax> $taxes
 * @property Customer|null $customer
 * @property Currency|null $currency
 * @property Company|null $company
 */
class Invoice extends Model
{
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SENT = 'SENT';

    public const STATUS_VIEWED = 'VIEWED';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_UNPAID = 'UNPAID';

    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';

    public const STATUS_PAID = 'PAID';

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

/**
 * @property int $id
 * @property string $name
 */
class Module extends Model {}

/**
 * @property int $id
 */
class User extends Model
{
    public function hasCompany(int|string $companyId): bool {}
}
