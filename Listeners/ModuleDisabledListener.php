<?php

declare(strict_types=1);

namespace Modules\Ecourier\Listeners;

use App\Events\ModuleDisabledEvent;
use Illuminate\Support\Facades\Schema;
use Modules\Ecourier\Models\EcourierRecipient;
use Modules\Ecourier\Models\EcourierSubmission;

/**
 * Clears module-owned data when the module is disabled.
 *
 * Documents already accepted by the network cannot be recalled, so there is
 * nothing external to undo — only local rows to drop. Guarded by `hasTable`
 * so it stays safe if the tables were already removed.
 */
class ModuleDisabledListener
{
    public function handle(ModuleDisabledEvent $event): bool
    {
        if ($event->module->name !== config('ecourier.name')) {
            return false;
        }

        foreach ([EcourierRecipient::class, EcourierSubmission::class] as $model) {
            $table = (new $model)->getTable();

            if (Schema::hasTable($table)) {
                $model::query()->delete();
            }
        }

        return true;
    }
}
