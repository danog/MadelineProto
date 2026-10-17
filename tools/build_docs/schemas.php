<?php declare(strict_types=1);

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use danog\MadelineProto\TL\TL;

/**
 * Load schema file names.
 * @internal
 */
function loadSchemas(): array
{
    $stored = trim(@file_get_contents(getcwd().'/schemas/last'));
    $page = 1;
    $had = [];
    // The layer of the newest schema: layer numbers aren't monotonic (tdesktop went from layer 230 to 231 and back).
    $current = null;
    $client = HttpClientBuilder::buildDefault();
    do {
        $request = new Request("https://api.github.com/repos/telegramdesktop/tdesktop/commits?path=Telegram/SourceFiles/mtproto/scheme/api.tl&per_page=100&page=$page");
        // Unauthenticated, the API allows 60 requests per hour per IP, shared by all of the jobs of a CI provider.
        if ($token = getenv('GITHUB_TOKEN')) {
            $request->setHeader('Authorization', "Bearer $token");
        }
        $res = $client->request($request)->getBody()->buffer();
        foreach (json_decode($res, true) as $idx => $commit) {
            $hash = $commit['sha'];
            if ($page === 1 && $idx === 0) {
                file_put_contents(getcwd().'/schemas/last', $hash);
            }
            $last = $hash === $stored && $current !== null
                ? ''
                : file_get_contents("https://raw.githubusercontent.com/telegramdesktop/tdesktop/$hash/Telegram/SourceFiles/mtproto/scheme/api.tl");
            if (preg_match("|// Layer (\d+)|i", $last, $matches)) {
                $current ??= (int) $matches[1];
            }
            if ($hash === $stored) {
                break 2;
            }
            if (!$matches) {
                continue;
            }
            $layer = (int) $matches[1];
            if (isset($had[$layer])) {
                continue;
            }
            $had[$layer] = true;
            file_put_contents(getcwd()."/schemas/TL_telegram_v{$layer}.tl", $last);
            echo "Stored layer $layer".PHP_EOL;
        }
        $page++;
    } while (true);

    // The schemas of the layers above the current one were abandoned, never served by Telegram.
    foreach (glob(getcwd().'/schemas/TL_telegram_v*') as $file) {
        if ($current !== null
            && preg_match("/telegram_v(\d+)/", $file, $matches)
            && (int) $matches[1] > $current
        ) {
            echo "Removing $file, above the current layer $current".PHP_EOL;
            unlink($file);
        }
    }

    $res = [];
    foreach (glob(getcwd().'/schemas/TL_telegram_*') as $file) {
        if (str_ends_with($file, '.tl')) {
            $tl = new TL(null);
            file_put_contents(str_replace('.tl', '.json', $file), json_encode($tl->toJson(file_get_contents($file)), JSON_PRETTY_PRINT));
        }
        preg_match("/telegram_v(\d+)/", $file, $matches);
        $res[$matches[1]] = $file;
    }
    ksort($res);
    file_put_contents(getcwd().'/schemas/list.json', json_encode(array_keys($res)));
    return $res;
}

/**
 * Return max available layer number.
 *
 * @param array $schemas Scheme array
 * @internal
 *
 * @return integer
 */
function maxLayer(array $schemas): int
{
    $schemas = array_keys($schemas);
    return end($schemas);
}
