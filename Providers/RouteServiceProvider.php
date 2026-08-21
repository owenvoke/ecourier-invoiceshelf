<?php

declare(strict_types=1);

namespace Modules\Ecourier\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Ecourier\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
    }

    /**
     * Define the "api" routes for the module.
     *
     * `auth:sanctum` and `company` mirror the host's own authenticated API, so
     * the request carries a user and a resolved company.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api/m/ecourier')
            ->middleware(['api', 'auth:sanctum', 'company'])
            ->namespace($this->moduleNamespace)
            ->group(module_path('Ecourier', '/Routes/api.php'));
    }
}
