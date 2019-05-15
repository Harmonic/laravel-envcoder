# laravel-envcoder

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

Encrypts your Laravel .env so that it can be stored in source control and decrypted via a password.

It was written to quickly and easily share .env variables via source control instead of having to manually pass around env. variables or look them up in various 3rd party services. 

Some highlights of the package include:

* Commit an encrypted version of your .env into source control to share with your team
* Written to be used in various automated deployment processes (password can be stored locally, conflict resolution) 
* Can be configured to overwrite, merge, or interactively decide how to deal with merge conflicts within your .env
* Does not require any changes to your project to retrieve .env variables
* Simply edit your .env files as normal (and encrypt them when you are ready to share)
* Compare your encrypted .env with the current one and see whats differences

## Installation

Via Composer

``` bash
$ composer require harmonic/laravel-envcoder --dev
```

Publish the config file (optional)
``` bash
php artisan vendor:publish --provider="harmonic\LaravelEnvcoder"
```

## Usage

### Encypting your .env

From your project root simply run:

``` bash
php artisan env:encrypt
```

You will be prompted for a password, if you prefer to enter it from the command line you can use

``` bash
php artisan env:encrypt --password=password
```
(replace password with your password)

### Decrypt your .env

From your project root simply run:

``` bash
php artisan env:decrypt
```

You will be prompted for a password, if you prefer to enter it from the command line you can use

``` bash
php artisan env:decrypt --password=password
```
(replace the second password with your password)

### Compare .env.enc with .env (Diff)

You can review any changes between your current .env and the encrypted one:

``` bash
php artisan env:compare --password=password
```
(replace the second password with your password)

### Include password in your .env file
You can add a variable to your .env file with the variable ENV_PASSWORD. This will be removed from the encrypted version but will allow simple encoding/decoding in development with no need for password. Simply add to your .env:

```
ENV_PASSWORD=passwordHere
```
(replace passwordHere with your password)

This way you will not be prompted for a password each time you encrypt/decrypt the .env file.

### Some usage suggestions

You may wish to have your production deployment script delete the .env.enc file from your server each time you deploy.

If you include the password in your .env file for local development you could add the env:decrypt command to your composer post-install section to automatically updte your .env file each time you do a composer install, eg.

```
"scripts": {
	"post-install-cmd": [
		"@php artisan env:decrypt"
	]
}
```

## Configuration

After publishing the config you can change the default behaviour for environment variable conflict resolution. To one of the following:

* 'merge' => Will merge changes in both files, and overwrite duplicates with what is in .env.enc (default)
* 'prompt' => Will prompt you for each value that has a different value in .env.enc vs .env or is not in both files
* 'overwrite' => Will completely overwrite your .env with what is in the encrypted version
* 'ignore' => Will ignore any changes in your encrypted .env (ie. will not decrypt)

See config/laravel-envcoder.php for more details.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ phpunit
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

We have selected the defuse\php-encryption package to handle the encrytion and decryption of the .env file due to its ease of use and security. With that said, storing an encrypted .env file in your source control is less than secure than not storing it at all. We believe only marginally, but it's up to you to weigh up the security vs. convience and make a decision for your project.

If you discover any security related issues, please email craig@harmonic.com.au instead of using the issue tracker.

## Credits

- [Craig Harman][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/harmonic/laravel-envcoder.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/harmonic/laravel-envcoder.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/harmonic/laravel-envcoder/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/182541287/shield

[link-packagist]: https://packagist.org/packages/harmonic/laravel-envcoder
[link-downloads]: https://packagist.org/packages/harmonic/laravel-envcoder
[link-travis]: https://travis-ci.org/harmonic/laravel-envcoder
[link-styleci]: https://styleci.io/repos/182541287
[link-author]: https://github.com/harmonic
[link-contributors]: ../../contributors
