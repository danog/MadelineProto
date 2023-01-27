<?php declare(strict_types=1);

/**
 * Upgrade layer number.
 *
 * @param integer $layer Layer number
 *
 */
function layerUpgrade(int $layer): void
{
    $doc = file_get_contents('docs/docs/docs/USING_METHODS.md');
    $doc = preg_replace('|here \(layer \d+\)|', "here (layer $layer)", $doc);
    file_put_contents('docs/docs/docs/USING_METHODS.md', $doc);

    array_map(unlink::class, glob('src/*.tl'));
    foreach (['TL_mtproto_v1', "TL_telegram_v$layer", 'TL_secret', 'TL_botAPI'] as $schema) {
        copy("schemas/$schema.tl", "src/$schema.tl");
    }

    $doc = file_get_contents('src/Settings/TLSchema.php');
    preg_match("/layer = (\d+)/", $doc, $matches);
    $prevLayer = (int) $matches[1];

    if ($prevLayer === $layer) {
        return;
    }

    $doc = str_replace(
        [
            "layer = $prevLayer",
            "TL_telegram_v$prevLayer",
        ],
        [
            "layer = $layer",
            "TL_telegram_v$layer",
        ],
        $doc
    );

    file_put_contents('src/Settings/TLSchema.php', $doc);
}
