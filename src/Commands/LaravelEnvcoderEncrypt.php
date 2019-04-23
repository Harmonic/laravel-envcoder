<?php

namespace harmonic\LaravelEnvcoder\Commands;

use harmonic\LaravelEnvcoder\LaravelEnvcoder;
use Illuminate\Console\Command;

class LaravelEnvcoderEncrypt extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:encrypt {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt your .env file.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $envcoder = new LaravelEnvcoder();
        $key = $envcoder->getPasswordFromEnv();
        if ($key === null) {
            $key = $this->option('password');
        }
        if ($key === false || $key === null) {
            $key = $this->password('Enter encryption key to encode .env');
        }
        $envcoder->encrypt($key);
        $this->info('.env encryption complete');
    }
}
