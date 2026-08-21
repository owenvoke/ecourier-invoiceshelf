<?php

declare(strict_types=1);

namespace Modules\Ecourier\Support;

use Ecourier\EcourierConnector;
use Modules\Ecourier\Peppol\PeppolMappingException;
use Modules\Ecourier\Settings\ModuleSettings;

/**
 * Builds a per-company eCourier connector.
 *
 * The API key is a per-company setting, and its `pk_test_` / `pk_live_` prefix
 * is what selects test or production mode, so there is nothing else to
 * configure here.
 */
class ConnectorFactory
{
    public function __construct(private ModuleSettings $settings) {}

    public function for(int $companyId): EcourierConnector
    {
        $apiKey = $this->settings->apiKey($companyId)
            ?? throw PeppolMappingException::missingSetting('api_key');

        return new EcourierConnector(apiKey: $apiKey);
    }
}
