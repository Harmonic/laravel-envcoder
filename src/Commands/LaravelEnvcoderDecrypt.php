<?php

namespace harmonic\LaravelEnvcoder\Commands;

use harmonic\LaravelEnvcoder\LaravelEnvcoder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class LaravelEnvcoderDecrypt extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:decrypt {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypt your .env file.';

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
            $key = $this->ask('Enter encryption key to decode .env');
        }

        $resolve = config('laravel-envcoder.resolve');

        try {
            $result = $envcoder->decrypt($key);

            if ($resolve === 'merge') {
                if ($result) {
                    $this->info('There were items in your .env not in env.enc, suggest you run php artisan env:encrypt');
                }
            } elseif ($resolve == 'prompt') {
                if (is_array($result)) {
                    $envFile = fopen('.env', 'w');
                    foreach ($result['decrypted'] as $key => $value) {
                        if (array_key_exists($key, $result['current']) && $value !== $result['current'][$key]) {
                            $use = $this->choice('Env variable ' . $key . ' has encrypted value (E) ' . $value . ' vs unencrypted value (U) ' . $result['current'][$key], ['E', 'U'], 0);
                            if ($use === 'E') {
                                fwrite($envFile, $key . '=' . $value . "\n");
                                continue;
                            }
                        } elseif (!array_key_exists($key, $result['current'])) {
                            $use = $this->choice('Env variable ' . $key . ' has encrypted value ' . $value . ' but does not exist in .env add (A) or skip (S)', ['A', 'S'], 0);
                            if ($use === 'S') {
                                continue;
                            }
                        }
                        fwrite($envFile, $key . '=' . $result['decrypted'][$key] . "\n");
                    }
                    $varsNotYetAdded = array_diff_key($result['current'], $result['decrypted']);
                    foreach ($varsNotYetAdded as $key => $value) {
                        $use = $this->choice('Env variable ' . $key . ' with value ' . $value . ' found in .env not in .env.enc add (A) or skip (S)', ['A', 'S'], 0);
                        if ($use === 'S') {
                            continue;
                        }
                        fwrite($envFile, $key . '=' . $value . "\n");
                    }

                    fclose($envFile);

                    if ($this->confirm('Do you wish to encrypt your newly generated .env?')) {
                        $this->call('env:encrypt', ['--password' => $key]);
                    }
                }
            }
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            $this->error('Unable to decrypt .env file please check your password.');
        } catch (FileNotFoundException $e) {
            $this->error('No encrypted .env file found. Try env:encrypt first.');
        }

        $this->call('config:clear');
        $this->info('.env decrytion complete');
    }
}
