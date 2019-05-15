# Changelog

All notable changes to `laravel-envcoder` will be documented in this file.

## Version 1.0.2

### Added
- env:compare command to show differences between .env and .env.enc

### Fixed
- Halt execution on errors from commands
- Change ask prompt to password to hide user input
- Allowed use of "" in env values
- Wrap strings containing spaces with "'s

## Version 1.0.1

### Fixed
- Handling of special characters in .env variables

### Modified
- Expanded readme.md with more instructions and default to require-dev for composer install
- Added warning when  no encrypted .env file is found

## Version 1.0

### Added
- Everything
