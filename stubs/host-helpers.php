<?php

declare(strict_types=1);

/** @see stubs/app-models.php for why these stubs exist. */

namespace Lavary\Menu {
    class Item
    {
        public function data(string $key, mixed $value = null): self {}
    }

    class Builder
    {
        public function add(string $title, array|string $options = ''): Item {}
    }
}

namespace {
    use Lavary\Menu\Builder;

    /**
     * The `Menu` facade alias registered by lavary/laravel-menu, which the host
     * application requires.
     */
    class Menu
    {
        /**
         * @param  callable(Builder): void  $callback
         */
        public static function make(string $name, callable $callback): Builder {}
    }

    /** Resolves a path inside an installed module. Provided by the host. */
    function module_path(string $name, string $path = ''): string {}
}
