<?php
namespace AvsquareTechnologies\Licenser;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use GuzzleHttp\Client;
use Dotenv\Dotenv;

class ComposerPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        // 1) Load the app’s .env (if it exists) from the project root
        if (file_exists(getcwd().'/.env')) {
            Dotenv::createImmutable(getcwd())->safeLoad();
        }

        // 2) Now we can safely read from getenv() or $_ENV
        $key     = getenv('APP_LICENSE_KEY') ?: ($_ENV['APP_LICENSE_KEY'] ?? '');
        $product = getenv('PRODUCT_SLUG')    ?: ($_ENV['PRODUCT_SLUG']    ?? '');
        $domain  = gethostname()             ?: '';
        $client  = new Client(['timeout' => 5]);

        try {
            $resp = $client->post('https://licenser.stageforav.com/api/validate', [
                'json' => compact('key','product','domain'),
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

    public function deactivate(Composer $composer, IOInterface $io): void { /*…*/ }
    public function uninstall(Composer $composer, IOInterface $io): void { /*…*/ }
}
