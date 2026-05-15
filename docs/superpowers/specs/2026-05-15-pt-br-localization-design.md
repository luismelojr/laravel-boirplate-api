# Design: pt-BR Localization Package for Laravel 13

## Context

This project is running `laravel/framework` `13.9.0` and currently uses the default locale values from `config/app.php`:

- `locale`: `en`
- `fallback_locale`: `en`
- `faker_locale`: `en_US`

The requested package is `lucascudo/laravel-pt-br-localization`.

## Compatibility Findings

- Composer dependency resolution accepts `lucascudo/laravel-pt-br-localization:^3.0` alongside Laravel 13 and resolves `v3.0.4`.
- The package README still documents official support only up to Laravel 12.
- The package exposes a Laravel service provider through package discovery.
- The provider publishes translation files into the application `lang/` directory under `pt_BR` and `pt_BR.json`.
- The package license is `GPL-3.0-or-later`.

## Decision

Install the package and configure the application to use Brazilian Portuguese by default with English as the fallback locale.

The package should be added as a development dependency because its purpose is to publish translation assets into the application. After publication, the application uses the copied files from `lang/` and does not need the package at runtime for translation loading.

## Implementation Plan

1. Install `lucascudo/laravel-pt-br-localization` as `require-dev`.
2. Ensure the application language scaffold exists with `artisan lang:publish` if needed.
3. Publish the package translations with the `laravel-pt-br-localization` tag.
4. Update `.env.example` to use `pt_BR` so the repository default is versioned:
   - `APP_LOCALE=pt_BR`
   - `APP_FALLBACK_LOCALE=en`
   - `APP_FAKER_LOCALE=pt_BR`
5. Update the current workspace `.env` to the same values so the running application switches to Portuguese immediately.
6. Keep `config/app.php` using environment-based configuration without structural changes.
7. Add or update an automated test that verifies the expected locale configuration.

## Verification

- Run the smallest relevant Pest test set for the locale/configuration change.
- Confirm the translation files were published into `lang/pt_BR` and `lang/pt_BR.json`.
- If PHP files change, run Pint in Sail.

## Risks and Constraints

- The package is installable with Laravel 13, but upstream documentation does not yet explicitly claim Laravel 13 support.
- The package uses a GPL license, which should remain an intentional choice by the project.
- If the package is later removed from `require-dev`, the published translation files should remain committed in the application so localization keeps working.
