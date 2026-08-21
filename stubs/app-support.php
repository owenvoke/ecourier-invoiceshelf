<?php

declare(strict_types=1);

/** @see stubs/app-models.php for why these stubs exist. */

namespace App\Events {
    use App\Models\Module;

    class ModuleDisabledEvent
    {
        /**
         * Untyped on the host; the dispatcher always passes the module record,
         * so it is narrowed here to keep call sites analysable.
         */
        public Module $module;
    }
}

namespace App\Services\Module {
    class ModuleFacade
    {
        public static function script(string $name, string $path): void {}

        public static function style(string $name, string $path): void {}
    }
}

namespace App\Http\Controllers {
    use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
    use Illuminate\Foundation\Bus\DispatchesJobs;
    use Illuminate\Foundation\Validation\ValidatesRequests;
    use Illuminate\Routing\Controller as BaseController;

    abstract class Controller extends BaseController
    {
        use AuthorizesRequests;
        use DispatchesJobs;
        use ValidatesRequests;
    }
}
