<?php

namespace harmonic\LaravelEnvcoder;

use harmonic\LaravelEnvcoder\Facades\LaravelEnvcoder as LEFacade;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Defuse\Crypto\File;

class LaravelEnvcoder {
    /**
     * Check in the ENV_PASSWORD .env variable exists and return it if so
     *
     * @return string if exists, null otherwise
     */
    public function getPasswordFromEnv() : ?string {
        $envArray = $this->envToArray('.env');
        if (array_key_exists('ENV_PASSWORD', $envArray)) {
            return $envArray['ENV_PASSWORD'];
        }

        return null;
    }

    /**
     * Encrypts the .env file and saves as .env.enc
     *
     * @param string $password The password used to encrypt the file
     * @return void
     */
    public function encrypt(string $password) : void {
        $needsPasswordAdded = false;
        if ($this->getPasswordFromEnv() !== null) {
            $contents = file_get_contents('.env');
            $contents = preg_replace('/ENV_PASSWORD=' . $password . '\R?/', '', $contents);
            file_put_contents('.env', $contents);
            $needsPasswordAdded = true;
        }
        File::encryptFileWithPassword('.env', '.env.enc', $password);
        if ($needsPasswordAdded) {
            $this->addPasswordToEnv($password);
        }
    }

    /**
     * Adds the ENV_PASSWORD back to the .env file
     * so you don't need to use it from artisan commmand each time.
     *
     * @param string $password
     * @return void
     */
    private function addPasswordToEnv(string $password) {
        $handle = fopen('.env', 'a');
        fwrite($handle, 'ENV_PASSWORD=' . $password . PHP_EOL);
        fclose($handle);
    }

    /**
     * Convert a .env file to an array of variables
     *
     * @param string $env
     * @return array
     */
    public function envToArray(string $envFile) : array {
        if (!is_file($envFile)) {
            return [];
        }
        return parse_ini_file($envFile, false, INI_SCANNER_RAW);
    }

    /**
     * Decrypt the .env.enc file using
     *
     * @param string $password The password used to encrypt the file
     * @return void
     */
    public function decrypt(string $password) {
        if (!\file_exists('.env.enc')) {
            throw new FileNotFoundException('No encrypted env file found.');
        }

        $resolve = config('laravel-envcoder.resolve');

        $needsPasswordAdded = false;
        if ($this->getPasswordFromEnv() !== null) {
            $needsPasswordAdded = true;
        }

        switch ($resolve) {
            case ('ignore'):
                return;
            case ('overwrite'):
                File::decryptFileWithPassword('.env.enc', '.env', $password);
                if ($needsPasswordAdded) {
                    $this->addPasswordToEnv($password);
                }
                return;
            case ('prompt'):
                File::decryptFileWithPassword('.env.enc', '.env.bak', $password);
                $decryptedArray = $this->envToArray('.env.bak');
                $currentEnv = $this->envToArray('.env');

                unlink('.env.bak');

                if ($needsPasswordAdded) {
                    $currentEnv['ENV_PASSWORD'] = $password;
                }

                return [
                    'decrypted' => $decryptedArray,
                    'current' => $currentEnv
                ];
            case ('merge'):
                File::decryptFileWithPassword('.env.enc', '.env.bak', $password);
                $decryptedArray = $this->envToArray('.env.bak');
                if ($needsPasswordAdded) {
                    $decryptedArray['ENV_PASSWORD'] = $password;
                }
                $currentEnv = $this->envToArray('.env');
                $mergedArray = array_merge($currentEnv, $decryptedArray);
                $envFile = fopen('.env', 'w');
                foreach ($mergedArray as $key => $value) {
                    $value = LEFacade::formatValue($value);
                    fwrite($envFile, $key . '=' . $value . PHP_EOL);
                }
                fclose($envFile);
                unlink('.env.bak');

                if (sizeof($mergedArray) > sizeof($decryptedArray)) {
                    return true; // let the calling function know something was merged and they potentially need to encrypt to sync
                }
                return;
            default:
                throw new \Exception('Invalid decryption conflict resolution, check your config file.');
        }
    }

    /**
     * Compare what is in the encoded file vs current .env
     *
     * @param string $password The password to decrypt the file
     * @return array $differences The elements that are different in the two env files
     *
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws FileNotFoundException
     */
    public function compare(string $password) : array {
        if (!\file_exists('.env.enc')) {
            throw new FileNotFoundException('No encrypted env file found.');
        }

        File::decryptFileWithPassword('.env.enc', '.env.bak', $password);

        $decryptedArray = $this->envToArray('.env.bak');
        $currentEnv = $this->envToArray('.env');

        $differences = array_diff_assoc($currentEnv, $decryptedArray);

        unlink('.env.bak');

        return $differences;
    }
}
