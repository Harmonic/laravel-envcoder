# laravel-envcoder

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

Encrypts your .env so that it can be stored in source control and decrypted via a password.

Also usable in CI/CD environments by storing the password as a variable in your CI/CD environment.

## Installation

Via Composer

``` bash
$ composer require harmonic/laravel-envcoder
```

Publish the config file (optional)
``` bash
php artisan vendor:publish --provider="harmonic\LaravelEnvcoder"
```

## Usage

# Encypting your .env

From your project root simply run:

``` bash
php artisan env:encrypt
```

You will be prompted for a password, if you prefer to enter it from the command line you can use

``` bash
php artisan env:encrypt --password=password
```
(replace password with your password)

# Decrypt your .env

From your project root simply run:

``` bash
php artisan env:decrypt
```

You will be prompted for a password, if you prefer to enter it from the command line you can use

``` bash
php artisan env:decrypt --p password
```
(replace password with your password)

# Include password in your .env file
You can add a variable to your .env file with the variable ENV_PASSWORD. This will be removed from the encrypted version but will allow simple encoding/decoding in development with no need for password. Simply add to your .env:

```
ENV_PASSWORD=passwordHere
```
(replace passwordHere with your password)

This way you will not be prompted for a password each time you encrypt/decrypt the .env file.


## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email craig@harmonic.com.au instead of using the issue tracker.

## Credits

- [Craig Harman][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/harmonic/laravel-envcoder.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/harmonic/laravel-envcoder.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/harmonic/laravel-envcoder/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/harmonic/laravel-envcoder
[link-downloads]: https://packagist.org/packages/harmonic/laravel-envcoder
[link-travis]: https://travis-ci.org/harmonic/laravel-envcoder
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/harmonic
[link-contributors]: ../../contributors
