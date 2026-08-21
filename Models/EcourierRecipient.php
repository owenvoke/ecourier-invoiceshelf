<?php

declare(strict_types=1);

namespace Modules\Ecourier\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's Peppol routing details.
 *
 * The host's customer record has no network participant identifier, so it is
 * stored here per company and customer.
 *
 * @property int $id
 * @property int $company_id
 * @property int $customer_id
 * @property string $scheme
 * @property string $identifier
 * @property string|null $name
 * @property string|null $vat_id
 * @property string|null $registration_number
 * @property string|null $street
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $country
 * @property Customer|null $customer
 */
class EcourierRecipient extends Model
{
    protected $table = 'ecourier_recipients';

    /** @var list<string> */
    protected $fillable = [
        'company_id',
        'customer_id',
        'scheme',
        'identifier',
        'name',
        'vat_id',
        'registration_number',
        'street',
        'city',
        'postal_code',
        'country',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
