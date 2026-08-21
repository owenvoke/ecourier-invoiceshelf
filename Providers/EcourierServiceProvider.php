<?php

declare(strict_types=1);

namespace Modules\Ecourier\Providers;

use App\Events\ModuleDisabledEvent;
use App\Models\Invoice;
use App\Services\Module\ModuleFacade;
use Illuminate\Support\ServiceProvider;
use Modules\Ecourier\Listeners\ModuleDisabledListener;
use Modules\Ecourier\Observers\InvoiceObserver;

class EcourierServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'Ecourier';

    /**
     * @var string
     */
    protected $moduleNameLower = 'ecourier';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerMenu();
        $this->registerPublicFiles();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        // Opt-in auto-send. The host fires no domain event when an invoice is
        // sent, so the module watches the model itself.
        Invoice::observe(InvoiceObserver::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['events']->listen(ModuleDisabledEvent::class, ModuleDisabledListener::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides()
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];

        /** @var array<int, string> $viewPaths */
        $viewPaths = config('view.paths', []);

        foreach ($viewPaths as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    /**
     * Add the module's settings screen to the host's settings sidebar.
     *
     * @return void
     */
    public function registerMenu()
    {
        $data = [
            'title' => 'ecourier.settings_title',
            'group' => '',
            'name' => 'eCourier',
            'link' => '/admin/settings/ecourier',
            'icon' => 'PaperAirplaneIcon',
            'owner_only' => true,
            'ability' => '',
            'model' => '',
        ];

        \Menu::make('setting_menu', function ($menu) use ($data) {
            $menu->add($data['title'], $data['link'])
                ->data('icon', $data['icon'])
                ->data('name', $data['name'])
                ->data('owner_only', $data['owner_only'])
                ->data('ability', $data['ability'])
                ->data('model', $data['model'])
                ->data('group', $data['group']);
        });
    }

    /**
     * Register public files.
     *
     * @return void
     */
    protected function registerPublicFiles()
    {
        ModuleFacade::script('ecourier', __DIR__.'/../dist/ecourier.umd.js');
        ModuleFacade::style('ecourier', __DIR__.'/../dist/style.css');
    }
}
