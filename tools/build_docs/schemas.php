<?php declare(strict_types=1);

/**
 * Load schema file names.
 *
 */
function loadSchemas(): array
{
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
 *
 * @return array Documentation information for old docs
 */
function initDocs(array $layers): array
{
    return [];
}
