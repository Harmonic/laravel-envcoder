<?php

namespace harmonic\LaravelEnvcoder\Commands;

use harmonic\LaravelEnvcoder\LaravelEnvcoder;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\File;

class LaravelEnvcoderCompare extends \harmonic\LaravelEnvcoder\LaravelEnvcoderBaseCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:compare {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare encoded .env with current .env';

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
        $key = $this->getPassword();
        $envcoder = new LaravelEnvcoder();
        try {
            $differences = $envcoder->compare($key);
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            $this->error('Unable to decrypt .env file please check your password.');
            return;
        }

        $headers = ['Key', '.env.enc', '.env'];
        $values = [];
        File::decryptFileWithPassword('.env.enc', '.env.bak', $key);
        $decryptedArray = $envcoder->envToArray('.env.bak');
        $currentEnv = $envcoder->envToArray('.env');
        foreach ($differences as $key => $value) {
            $encValue = '-';
            $decValue = '-';
            if (!array_key_exists($key, $decryptedArray)) {
                $decValue = $value;
            } elseif (!array_key_exists($key, $currentEnv)) {
                $encValue = $value;
            } else {
                $encValue = $decryptedArray[$key];
                $decValue = $currentEnv[$key];
            }
            $values[] = [$key, $encValue, $decValue];
        }
        unlink('.env.bak');
        //dd($differences);
        $this->table($headers, $values);
    }
}
