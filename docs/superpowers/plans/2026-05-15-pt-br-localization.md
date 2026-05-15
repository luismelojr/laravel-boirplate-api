# pt-BR Localization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Install `lucascudo/laravel-pt-br-localization`, publish its translation files into the application, and switch the project defaults to Brazilian Portuguese with automated verification.

**Architecture:** Keep localization runtime behavior environment-driven through `config/app.php`, but version the project defaults in `.env.example`, update the current workspace `.env` for immediate local effect, and pin testing locale variables in `phpunit.xml` so the automated test is deterministic. Treat the package as a development dependency because the application will read the published files from `lang/` after installation.

**Tech Stack:** Laravel 13, Laravel Sail, Composer, Pest, PHPUnit XML, `lucascudo/laravel-pt-br-localization`

---

## Planned File Changes

- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `.env.example`
- Modify: `.env`
- Modify: `phpunit.xml`
- Create: `tests/Feature/Localization/PortugueseLocalizationTest.php`
- Create: `lang/pt_BR/`
- Create: `lang/pt_BR.json`

### Task 1: Write the failing localization regression test

**Files:**
- Create: `tests/Feature/Localization/PortugueseLocalizationTest.php`
- Test: `tests/Feature/Localization/PortugueseLocalizationTest.php`

- [ ] **Step 1: Create the localization feature test**

```php
<?php

it('uses pt_BR as the configured locale with English fallback across the application', function () {
    expect(config('app.locale'))->toBe('pt_BR');
    expect(config('app.fallback_locale'))->toBe('en');
    expect(config('app.faker_locale'))->toBe('pt_BR');
});

it('resolves Laravel translation keys using Brazilian Portuguese at runtime', function () {
    app()->setLocale('pt_BR');

    expect(__('pagination.next'))->toBe('Próximo &raquo;');
});

it('publishes Brazilian Portuguese translation files into the application lang directory', function () {
    expect(is_dir(lang_path('pt_BR')))->toBeTrue();
    expect(file_exists(lang_path('pt_BR/pagination.php')))->toBeTrue();
    expect(file_exists(lang_path('pt_BR.json')))->toBeTrue();
});

it('documents pt_BR defaults in the example environment file', function () {
    $environmentExample = file_get_contents(base_path('.env.example'));

    expect($environmentExample)->not->toBeFalse();
    expect($environmentExample)->toContain('APP_LOCALE=pt_BR');
    expect($environmentExample)->toContain('APP_FALLBACK_LOCALE=en');
    expect($environmentExample)->toContain('APP_FAKER_LOCALE=pt_BR');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `vendor/bin/sail artisan test --compact tests/Feature/Localization/PortugueseLocalizationTest.php`

Expected: FAIL because `config('app.locale')` is still `en`, `config('app.faker_locale')` is still `en_US`, the `pagination.next` translation is not yet published in `pt_BR`, `.env.example` still declares the old locale defaults, and `lang/pt_BR` / `lang/pt_BR.json` do not exist yet.

### Task 2: Install the package and configure project defaults

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `.env.example`
- Modify: `.env`
- Modify: `phpunit.xml`
- Create: `lang/pt_BR/`
- Create: `lang/pt_BR.json`
- Test: `tests/Feature/Localization/PortugueseLocalizationTest.php`

- [ ] **Step 1: Install the localization package as a dev dependency**

Run: `vendor/bin/sail composer require lucascudo/laravel-pt-br-localization:^3.0 --dev --no-interaction`

Expected: Composer adds the package under `require-dev`, updates `composer.lock`, installs `v3.0.4` or a newer compatible `3.x` release, and completes package discovery without Laravel 13 conflicts.

- [ ] **Step 2: Publish the pt-BR translation files into the application**

Run: `vendor/bin/sail artisan vendor:publish --tag=laravel-pt-br-localization --no-interaction`

Expected: Laravel copies the package resources into `lang/pt_BR/` and `lang/pt_BR.json`.

- [ ] **Step 3: Update `.env.example` to version the repository defaults**

Set these lines in `/home/luis/code/boirplates/laravel-api/.env.example`:

```dotenv
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR
```

- [ ] **Step 4: Update `.env` so the local application switches immediately**

Set these lines in `/home/luis/code/boirplates/laravel-api/.env`:

```dotenv
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR
```

- [ ] **Step 5: Pin the test environment locale in `phpunit.xml`**

Insert these entries inside the `<php>` block in `/home/luis/code/boirplates/laravel-api/phpunit.xml` near the existing `APP_ENV` declaration:

```xml
<env name="APP_ENV" value="testing"/>
<env name="APP_LOCALE" value="pt_BR"/>
<env name="APP_FALLBACK_LOCALE" value="en"/>
<env name="APP_FAKER_LOCALE" value="pt_BR"/>
```

- [ ] **Step 6: Clear cached configuration after the environment changes**

Run: `vendor/bin/sail artisan config:clear --no-interaction`

Expected: `Configuration cache cleared successfully.`

### Task 3: Verify the change set and format the PHP test file

**Files:**
- Modify: `tests/Feature/Localization/PortugueseLocalizationTest.php` only if formatting changes are applied
- Modify: dirty published PHP language files under `lang/pt_BR/` only if formatting changes are applied
- Test: `tests/Feature/Localization/PortugueseLocalizationTest.php`

- [ ] **Step 1: Run the focused localization test and make sure it passes**

Run: `vendor/bin/sail artisan test --compact tests/Feature/Localization/PortugueseLocalizationTest.php`

Expected: PASS with all four focused localization tests succeeding.

- [ ] **Step 2: Run Pint because a PHP test file was added**

Run: `vendor/bin/sail bin pint --dirty --format agent`

Expected: Pint completes successfully and only applies style adjustments if needed.

- [ ] **Step 3: Re-run the focused localization test after formatting**

Run: `vendor/bin/sail artisan test --compact tests/Feature/Localization/PortugueseLocalizationTest.php`

Expected: PASS again, confirming formatting did not change behavior.

- [ ] **Step 4: Syntax-check any published PHP language files touched by Pint**

If `vendor/bin/sail bin pint --dirty --format agent` modifies published PHP language files under `lang/pt_BR/`, run `vendor/bin/sail php -l` against each touched file.

Expected: Every touched published PHP language file reports `No syntax errors detected`, providing explicit verification coverage for PHP files changed only by formatting.
