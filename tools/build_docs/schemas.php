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
    $client = HttpClientBuilder::buildDefault();
    do {
        $res = $client->request(new Request("https://api.github.com/repos/telegramdesktop/tdesktop/commits?path=Telegram/SourceFiles/mtproto/scheme/api.tl&per_page=100&page=$page"))->getBody()->buffer();
        foreach (json_decode($res, true) as $idx => $commit) {
            $hash = $commit['sha'];
            if ($page === 1 && $idx === 0) {
                file_put_contents(getcwd().'/schemas/last', $hash);
            }
            if ($hash === $stored) {
                break 2;
            }
            $last = file_get_contents("https://raw.githubusercontent.com/telegramdesktop/tdesktop/$hash/Telegram/SourceFiles/mtproto/scheme/api.tl");
            if (!preg_match("|// Layer (\d+)|i", $last, $matches)) {
                continue;
            }
            $layer = $matches[1];
            if (isset($had[$layer])) {
                continue;
            }
            $had[$layer] = true;
            file_put_contents(getcwd()."/schemas/TL_telegram_v{$layer}.tl", $last);
            echo "Stored layer $layer".PHP_EOL;
        }
        $page++;
    } while (true);

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
