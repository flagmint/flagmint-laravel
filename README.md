# flagmint/laravel

Laravel bindings for Flagmint (provider, facade, Blade `@feature`, middleware, Artisan).

```bash
composer require flagmint/laravel
php artisan vendor:publish --tag=flagmint-config
```

```env
FLAGMINT_SDK_KEY=fm_sdk_...
FLAGMINT_CACHE_DRIVER=laravel
```

```php
use Flagmint\Laravel\Facades\Flagmint;

if (Flagmint::bool('new-checkout')) {
    // …
}
```

```blade
@feature('new-checkout')
  New checkout
@else
  Classic checkout
@endfeature
```

Default cache driver is Laravel Cache (shared across FPM workers when Redis/Memcached is configured).

## Docs

- Product docs: [docs.flagmint.com/sdks/laravel](https://docs.flagmint.com/sdks/laravel)
- Core PHP SDK: [flagmint/php-sdk](https://github.com/flagmint/flagmint-php-sdk)
- Changelog: [CHANGELOG.md](CHANGELOG.md)
