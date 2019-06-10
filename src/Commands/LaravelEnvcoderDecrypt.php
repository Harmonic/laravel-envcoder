<?php

namespace harmonic\LaravelEnvcoder\Commands;

use harmonic\LaravelEnvcoder\LaravelEnvcoder;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use harmonic\LaravelEnvcoder\Facades\LaravelEnvcoder as LEFacade;

class LaravelEnvcoderDecrypt extends \harmonic\LaravelEnvcoder\LaravelEnvcoderBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:decrypt {--p|password=} {--s|source=.env.enc}';

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
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $key = $this->getPassword();
        $envcoder = new LaravelEnvcoder();
        $resolve = config('laravel-envcoder.resolve');
        $sourceEnv = $this->option('source');

        try {
            $result = $envcoder->decrypt($key, $sourceEnv);

            if ($resolve === 'merge') {
                if ($result) {
                    $this->info('There were items in your .env not in env.enc, suggest you run php artisan env:compare and/or env:encrypt to update .env.enc');
                }
            } elseif ($resolve == 'prompt') {
                if (is_array($result)) {
                    $envFile = fopen('.env', 'w');
                    foreach ($result['decrypted'] as $key => $value) {
                        if (array_key_exists($key, $result['current']) && $value !== $result['current'][$key]) {
                            $use = $this->choice('Env variable '.$key.' has encrypted value (E) '.$value.' vs unencrypted value (U) '.$result['current'][$key], ['E', 'U'], 0);
                            if ($use === 'E') {
                                $value = LEFacade::formatValue($value);
                                fwrite($envFile, $key.'='.$value.PHP_EOL);
                                continue;
                            }
                        } elseif (! array_key_exists($key, $result['current'])) {
                            $use = $this->choice('Env variable '.$key.' has encrypted value '.$value.' but does not exist in .env add (A) or skip (S)', ['A', 'S'], 0);
                            if ($use === 'S') {
                                continue;
                            }
                        }
                        fwrite($envFile, $key.'='.$result['decrypted'][$key].PHP_EOL);
                    }
                    $varsNotYetAdded = array_diff_key($result['current'], $result['decrypted']);
                    foreach ($varsNotYetAdded as $key => $value) {
                        $use = $this->choice('Env variable '.$key.' with value '.$value.' found in .env not in .env.enc add (A) or skip (S)', ['A', 'S'], 0);
                        if ($use === 'S') {
                            continue;
                        }
                        $value = LEFacade::formatValue($value);
                        fwrite($envFile, $key.'='.$value.PHP_EOL);
                    }

                    fclose($envFile);

                    if ($this->confirm('Do you wish to encrypt your newly generated .env?')) {
                        $this->call('env:encrypt', ['--password' => $key]);
                    }
                }
            }
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            $this->error('Unable to decrypt .env file please check your password.');

            return;
        } catch (FileNotFoundException $e) {
            $this->error('No encrypted .env file found. Try env:encrypt first.');

            return;
        }

        $this->call('config:clear');
        $this->info('.env decrytion complete');
    }
}
