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

    /**
     * Takes a .env value and formats it correctly (double quotes, spaces etc)
     *
     * @param string $value The unformatted .env value
     * @return string The formatted value
     */
    public static function formatValue(string $value) : string {
        if (strpos($value, ' ') !== false) {
            $value = '"' . $value . '"'; // Wrap values in quotes as required
        }
        return $value;
    }
}
