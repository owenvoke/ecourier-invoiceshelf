<?php

declare(strict_types=1);

return [
    'name' => 'Ecourier',

    /*
     * Company settings this module owns. Every key is prefixed so it cannot
     * collide with a host setting, and the settings controller writes only
     * keys listed here.
     */
    'settings' => [
        'ecourier_enabled',
        'ecourier_auto_send',
        'ecourier_api_key',
        'ecourier_channel',
        'ecourier_sender_scheme',
        'ecourier_sender_id',
        'ecourier_sender_vat_id',
        'ecourier_sender_registration_number',
        'ecourier_tax_category',
        'ecourier_tax_percent',
        'ecourier_unit_code',
        'ecourier_payment_means_code',
        'ecourier_account_scheme',
        'ecourier_account_id',
        'ecourier_bank_id',
        'ecourier_bank_name',
        'ecourier_payment_terms_note',
    ],
];
