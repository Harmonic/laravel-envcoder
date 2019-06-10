<?php

use Defuse\Crypto\File;
use harmonic\LaravelEnvcoder\Facades\LaravelEnvcoder;
use harmonic\LaravelEnvcoder\LaravelEnvcoder as LEObj;

class LaravelEnvcoderTest extends Orchestra\Testbench\TestCase
{
    /**
     * load your package service provider.
     *
     * @param [type] $app
     * @return void
     */
    protected function getPackageProviders($app)
    {
        return ['harmonic\LaravelEnvcoder\LaravelEnvcoderServiceProvider'];
    }

    /**
     * Resolve application Console Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Console\Kernel', 'Orchestra\Testbench\Console\Kernel');
    }

    /**
     * Create a test array of env variables.
     *
     * @return array
     */
    private function createEnvArray() : array
    {
        $envArray = [
            'VAR1' => 'TEST',
            'VAR2' => 'TEST2',
        ];

        return $envArray;
    }

    private function createEnvArrayWithPassword() : array
    {
        $envArray = $this->createEnvArray();
        $envArray['ENV_PASSWORD'] = 'password';

        return $envArray;
    }

    /**
     * Create a .env file.
     *
     * @param array $envArray Key/Value array to make into .env file
     * @return void
     */
    private function arrayToEnvFile(array $envArray) : void
    {
        $envFile = fopen('.env', 'w');

        foreach ($envArray as $key => $value) {
            $value = LaravelEnvcoder::formatValue($value);
            fwrite($envFile, $key.'='.$value.PHP_EOL);
        }
        fclose($envFile);
    }

    /**
     * Create .env file with no password inside it.
     *
     * @return void
     */
    private function createEnvFile() : void
    {
        $envArray = $this->createEnvArray();
        $this->arrayToEnvFile($envArray);
    }

    /**
     * Create env file with a password inside it.
     *
     * @return void
     */
    private function createEnvFileWithPassword() : void
    {
        $envArray = $this->createEnvArrayWithPassword();
        $this->arrayToEnvFile($envArray);
    }

    /**
     * Test Description.
     *
     * @test
     * @return void
     */
    public function canEncryptEnv()
    {
        // Arrange
        $this->createEnvFileWithPassword();

        // Act
        $this->artisan('env:encrypt');
        File::decryptFileWithPassword('.env.enc', '.env.decrypted', 'password');

        // Add the ENV_PASSWORD to .env.decrypted to fake it
        $handle = fopen('.env.decrypted', 'a');
        fwrite($handle, 'ENV_PASSWORD=password'.PHP_EOL);
        fclose($handle);

        // Assert
        $this->assertTrue(file_exists('.env.enc'));
        $this->assertEquals(file_get_contents('.env'), file_get_contents('.env.decrypted'));

        unlink('.env');
        unlink('.env.enc');
        unlink('.env.decrypted');
    }

    /**
     * Can encypt a .env.testing file
     *
     * @test
     * @return void
     */
    public function canEncryptEnvTesting()
    {
        // Arrange
        $this->createEnvFileWithPassword();
        copy('.env', 'env.testing');
        unlink('.env');

        // Act
        $this->artisan('env:encrypt --source .env.testing');
        copy('.env.testing', '.env.testing.original');
        unlink('.env.testing');
        $this->artisan('env:decrupt --p password --source .env.testing');

        // Add the ENV_PASSWORD to .env.decrypted to fake it
        $handle = fopen('.env.decrypted', 'a');
        fwrite($handle, 'ENV_PASSWORD=password'.PHP_EOL);
        fclose($handle);

        // Assert
        $this->assertTrue(file_exists('.env.testing.enc'));
        $this->assertTrue(file_exists('.env.testing'));
        $this->assertEquals(file_get_contents('.env.testing.original'), file_get_contents('.env.testing'));

        unlink('.env.testing');
        unlink('.env.testing.enc');
        unlink('.env.testing.original');
    }

    /**
     * Can use password option and prompt for password.
     *
     * @test
     * @return void
     */
    public function willAskForPassword()
    {
        // Arrange
        $this->createEnvFile();

        // Act and Assert
        $this->artisan('env:encrypt')->expectsQuestion('Enter encryption key to encode .env', 'password')->assertExitCode(0);

        unlink('.env');
    }

    /**
     * Can use password as a param.
     *
     * @test
     * @return void
     */
    public function willUseParamForPassword()
    {
        // Arrange
        $this->createEnvFile();

        // Act and Assert
        $this->artisan('env:encrypt --password=password')->assertExitCode(0);

        unlink('.env');
    }

    /**
     * Test decryption method with overwrite.
     *
     * @test
     * @return void
     */
    public function canDecryptOverwrite()
    {
        // Arrange
        $this->createEnvFile();
        File::encryptFileWithPassword('.env', '.env.enc', 'password');
        Config::set('laravel-envcoder.resolve', 'overwrite');
        $originalEnv = file_get_contents('.env');

        // Act
        unlink('.env');
        $this->artisan('env:decrypt --password=password')->assertExitCode(0);

        // Assert
        $this->assertEquals(file_get_contents('.env'), $originalEnv);
    }

    /**
     * Test decryption method with ignore.
     *
     * @test
     * @return void
     */
    public function canDecryptIgnore()
    {
        // Arrange
        Config::set('laravel-envcoder.resolve', 'ignore');
        $this->createEnvFile();
        $originalEnv = 'VAR1=TEST'.PHP_EOL.'VAR2=TEST2'.PHP_EOL;

        $env2 = fopen('.env2', 'w');
        fwrite($env2, 'VAR3=TEST3'.PHP_EOL.'VAR4=TEST4'.PHP_EOL);
        fclose($env2);
        File::encryptFileWithPassword('.env2', '.env.enc', 'password');

        // Act
        $this->artisan('env:decrypt --password=password')->assertExitCode(0);

        // Assert
        $this->assertEquals($originalEnv, file_get_contents('.env'));

        unlink('.env');
    }

    /**
     * Test decryption method with merge.
     *
     * @test
     * @return void
     */
    public function canDecryptMerge()
    {
        // Arrange
        $this->createEnvFile();
        Config::set('laravel-envcoder.resolve', 'merge');

        $env2 = fopen('.env2', 'w');
        fwrite($env2, 'VAR3=TEST3'.PHP_EOL.'VAR4=TEST4'.PHP_EOL);
        fclose($env2);
        File::encryptFileWithPassword('.env2', '.env.enc', 'password');

        $finalFile = 'VAR1=TEST'.PHP_EOL.'VAR2=TEST2'.PHP_EOL.'VAR3=TEST3'.PHP_EOL.'VAR4=TEST4'.PHP_EOL;

        // Act
        $this->artisan('env:decrypt --password=password')->assertExitCode(0);

        // Assert
        $this->assertEquals($finalFile, file_get_contents('.env'));

        unlink('.env');
        unlink('.env.enc');
        unlink('.env2');
    }

    /**
     * Test decryption method with prompt.
     *
     * @test
     * @return void
     */
    public function canDecryptPrompt()
    {
        // Arrange
        $this->createEnvFileWithPassword();
        Config::set('laravel-envcoder.resolve', 'prompt');

        $env2 = fopen('.env2', 'w');
        fwrite($env2, 'VAR1=TEST3'.PHP_EOL.'VAR3=TEST4'.PHP_EOL.'ENV_PASSWORD=password');
        fclose($env2);
        File::encryptFileWithPassword('.env2', '.env.enc', 'password');

        $finalFile = 'VAR1=TEST3'.PHP_EOL.'VAR3=TEST4'.PHP_EOL.'VAR2=TEST2'.PHP_EOL.'ENV_PASSWORD=password'.PHP_EOL; // use encrypted, add 3

        // Act
        $this->artisan('env:decrypt --password=password')
            ->expectsQuestion('Env variable VAR1 has encrypted value (E) TEST3 vs unencrypted value (U) TEST', 'E')
            ->expectsQuestion('Env variable VAR3 has encrypted value TEST4 but does not exist in .env add (A) or skip (S)', 'A')
            ->expectsQuestion('Env variable VAR2 with value TEST2 found in .env not in .env.enc add (A) or skip (S)', 'A')
            ->expectsQuestion('Do you wish to encrypt your newly generated .env?', 'N')
            ->assertExitCode(0);

        // Assert
        $this->assertEquals($finalFile, file_get_contents('.env'));

        unlink('.env');
        unlink('.env.enc');
        unlink('.env2');
    }

    /**
     * Test long values with spaces are correctly enc and decrypted.
     *
     * @test
     * @return void
     */
    public function handlesLongValues()
    {
        // Arrange
        $env = fopen('.env', 'w');
        fwrite($env, 'LONGVAR="This is a long var"'.PHP_EOL.'SHORTVAR=TEST4'.PHP_EOL);
        fclose($env);
        File::encryptFileWithPassword('.env', '.env.enc', 'password');

        $this->artisan('env:decrypt --password=password')->assertExitCode(0);

        // Assert
        $this->assertEquals('LONGVAR="This is a long var"'.PHP_EOL.'SHORTVAR=TEST4'.PHP_EOL, file_get_contents('.env'));

        unlink('.env');
        unlink('.env.enc');
    }

    /**
     * Test comparison command.
     *
     * @test
     * @return void
     */
    public function correctlyComparesEnvs()
    {
        // Arrange
        $this->createEnvFile();
        $this->artisan('env:encrypt --password=password');
        $env = fopen('.env', 'w');
        fwrite($env, 'VAR1=CHANGED'.PHP_EOL.'VAR2=TEST2'.PHP_EOL.'VAR3=NEW');
        fclose($env);

        // Act
        $envcoder = new LEObj();
        $differences = $envcoder->compare('password');

        // Assert
        $this->assertEquals('CHANGED', $differences['VAR1']);
        $this->assertEquals('NEW', $differences['VAR3']);
        $this->artisan('env:compare --password=password')->assertExitCode(0);

        unlink('.env');
        unlink('.env.enc');
    }
}
