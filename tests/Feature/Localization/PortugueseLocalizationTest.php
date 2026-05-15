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
