<?php declare(strict_types=1);

/**
 * Load schema file names.
 * @internal
 */
function loadSchemas(): array
{
    $last = file_get_contents("https://raw.githubusercontent.com/telegramdesktop/tdesktop/dev/Telegram/SourceFiles/mtproto/scheme/api.tl");
    preg_match("|// Layer (\d+)|i", $last, $matches);
    file_put_contents(getcwd()."/schemas/TL_telegram_v{$matches[1]}.tl", $last);

    $res = [];
    foreach (glob(getcwd().'/schemas/TL_telegram_*') as $file) {
        preg_match("/telegram_v(\d+)/", $file, $matches);
        $res[$matches[1]] = $file;
    }
    ksort($res);
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

/**
 * Init docs.
 *
 * @param array $layers Scheme array
 * @internal
 *
 * @return array Documentation information for old docs
 */
function initDocs(array $layers): array
{
    return [];
}
