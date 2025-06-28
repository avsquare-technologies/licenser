<?php
namespace AvsquareTechnologies\Licenser;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use GuzzleHttp\Client;

class ComposerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        $key     = getenv('APP_LICENSE_KEY') ?: '';
        $product = getenv('PRODUCT_SLUG')    ?: '';
        $domain  = gethostname()             ?: '';
        $client  = new Client(['timeout' => 5]);

        try {
            $resp = $client->post('http://127.0.0.1:8000/api/validate', [
                'json' => compact('key', 'product', 'domain'),
            ]);
            $data = json_decode((string) $resp->getBody(), true);
            if (! ($data['valid'] ?? false)) {
                $io->writeError('ERROR: Invalid Avsquare license for ' . $domain);
                exit(1);
            }
        } catch (\Throwable $e) {
            $io->writeError('ERROR: License validation failed: ' . $e->getMessage());
            exit(1);
        }
    }
}
