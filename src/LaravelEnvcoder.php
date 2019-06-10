<?php

namespace harmonic\LaravelEnvcoder;

use Defuse\Crypto\File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use harmonic\LaravelEnvcoder\Facades\LaravelEnvcoder as LEFacade;

class LaravelEnvcoder {
    /**
     * Check in the ENV_PASSWORD .env variable exists and return it if so.
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
     * Encrypts the .env file and saves as .env.enc.
     *
     * @param string $password The password used to encrypt the file
     * @param string $sourceEnv The name of the .env file to encrypt
     * @return void
     */
    public function encrypt(string $password, string $sourceEnv) : void {
        $needsPasswordAdded = false;
        if ($this->getPasswordFromEnv() !== null) {
            $contents = file_get_contents($sourceEnv);
            $contents = preg_replace('/ENV_PASSWORD=' . $password . '\R?/', '', $contents);
            file_put_contents($sourceEnv, $contents);
            $needsPasswordAdded = true;
        }
        File::encryptFileWithPassword($sourceEnv, $sourceEnv . '.enc', $password);
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
     * Convert a .env file to an array of variables.
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
     * Decrypt the .env.enc file using.
     *
     * @param string $password The password used to encrypt the file
     * @param string $sourceEnv The name of the encrypted .env file to decrypt
     * @return void
     */
    public function decrypt(string $password, string $sourceEnv) {
        if (!\file_exists($sourceEnv)) {
            throw new FileNotFoundException('No encrypted env file found.');
        }

        $resolve = config('laravel-envcoder.resolve');

        $needsPasswordAdded = false;
        if ($this->getPasswordFromEnv() !== null) {
            $needsPasswordAdded = true;
        }

        $sourceEnvUnencrypted = substr($sourceEnv, 0, -4);

        switch ($resolve) {
            case 'ignore':
                return;
            case 'overwrite':
                File::decryptFileWithPassword($sourceEnv, $sourceEnvUnencrypted, $password);
                if ($needsPasswordAdded) {
                    $this->addPasswordToEnv($password);
                }

                return;
            case 'prompt':
                $envBak = $sourceEnvUnencrypted . '.bak';
                File::decryptFileWithPassword($sourceEnv, $envBak, $password);
                $decryptedArray = $this->envToArray($envBak);
                $currentEnv = $this->envToArray($sourceEnvUnencrypted);

                unlink($envBak);

                if ($needsPasswordAdded) {
                    $currentEnv['ENV_PASSWORD'] = $password;
                }

                return [
                    'decrypted' => $decryptedArray,
                    'current' => $currentEnv,
                ];
            case 'merge':
                $envBak = $sourceEnvUnencrypted . '.bak';
                File::decryptFileWithPassword($sourceEnv, $envBak, $password);
                $decryptedArray = $this->envToArray($envBak);
                if ($needsPasswordAdded) {
                    $decryptedArray['ENV_PASSWORD'] = $password;
                }
                $currentEnv = $this->envToArray($sourceEnvUnencrypted);
                $mergedArray = array_merge($currentEnv, $decryptedArray);
                $envFile = fopen($sourceEnvUnencrypted, 'w');
                foreach ($mergedArray as $key => $value) {
                    $value = LEFacade::formatValue($value);
                    fwrite($envFile, $key . '=' . $value . PHP_EOL);
                }
                fclose($envFile);
                unlink($envBak);

                if (count($mergedArray) > count($decryptedArray)) {
                    return true; // let the calling function know something was merged and they potentially need to encrypt to sync
                }

                return;
            default:
                throw new \Exception('Invalid decryption conflict resolution, check your config file.');
        }
    }

    /**
     * Compare what is in the encoded file vs current .env.
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
