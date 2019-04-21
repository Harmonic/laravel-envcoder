<?php

namespace harmonic\LaravelEnvcoder\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelEnvcoder extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'LaravelEnvcoder';
    }
}
