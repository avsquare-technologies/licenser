<?php
namespace AvsquareTechnologies\Licenser;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;

class LicenserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $key     = config('app.license_key');
        $product = config('app.product_slug');
        $domain  = request()->getHost();

    // 1) CLI override via .env (for composer, artisan, etc.)
    $domain = env('LICENSE_DOMAIN');

    // 2) Next try parsing APP_URL from .env
    if (! $domain && config('app.url')) {
        $domain = parse_url(config('app.url'), PHP_URL_HOST);
    }

    // 3) Then try the incoming HTTP request
    if (! $domain && request()?->getHost()) {
        $domain = request()->getHost();
    }

    // 4) Last resort: machine hostname
    $domain = $domain ?: gethostname();

        if (! $key || ! $this->validate($key, $product, $domain)) {
            abort(403, 'Invalid or missing license for ' . $domain);
        }
    }

    protected function validate(string $key, string $product, string $domain): bool
    {
        $cacheKey = "avsquare_license_{$key}_{$product}_{$domain}";

        return cache()->remember($cacheKey, 1440, function () use ($key, $product, $domain) {
            $client = new Client(['timeout' => 5]);
            $resp   = $client->post('http://127.0.0.1:8000/api/validate', [
                'json' => compact('key', 'product', 'domain'),
            ]);

            if ($resp->getStatusCode() !== 200) {
                return false;
            }

            return data_get(json_decode((string) $resp->getBody(), true), 'valid', false);
        });
    }
}
