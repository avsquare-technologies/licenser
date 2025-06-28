<?php
namespace Avsquare\Licenser;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;

class LicenserServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $key     = config('app.license_key');
        $product = config('app.product_slug');
        $domain  = request()->getHost();

        if (! $key || ! $this->validate($key, $product, $domain)) {
            abort(403, 'Invalid or missing license for ' . $domain);
        }
    }

    protected function validate(string $key, string $product, string $domain): bool
    {
        $cacheKey = "avsquare_license_{$key}_{$product}_{$domain}";

        return cache()->remember($cacheKey, 1440, function () use ($key, $product, $domain) {
            $client = new Client(['timeout'=>5]);
            $resp   = $client->post('https://licenses.avsquare.com/api/validate', [
                'json' => compact('key','product','domain'),
            ]);

            if ($resp->getStatusCode() !== 200) {
                return false;
            }

            return data_get(json_decode($resp->getBody(), true), 'valid', false);
        });
    }
}
