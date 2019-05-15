<?php

namespace harmonic\LaravelEnvcoder;

use harmonic\LaravelEnvcoder\LaravelEnvcoder;
use Illuminate\Console\Command;

abstract class LaravelEnvcoderBaseCommand extends Command {
    public function __construct() {
        parent::__construct();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Try multiple methods to get the password for decrypting .env.enc
     *
     * @return string $key The password
     */
    public function getPassword() {
        $envcoder = new LaravelEnvcoder();
        $key = $envcoder->getPasswordFromEnv();
        if ($key === null) {
            $key = $this->option('password');
        }
        if ($key === false || $key === null) {
            $key = $this->secret('Enter encryption key to decode .env');
        }

        // When running from composer the prompt will not appear, so error
        if ($key === null || $key === false) {
            $this->error('Password cannot be resolved add as command option or into your .env as ENV_PASSWORD');
            return; // Halt execution
        }

        return $key;
    }
}
