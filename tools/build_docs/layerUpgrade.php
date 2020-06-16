<?php

/**
 * Upgrade layer number.
 *
 * @param integer $layer Layer number
 *
 * @return void
 */
function layerUpgrade(int $layer): void
{
    $doc = \file_get_contents('docs/docs/docs/USING_METHODS.md');
    $doc = \preg_replace('|here \(layer \d+\)|', "here (layer $layer)", $doc);
    \file_put_contents('docs/docs/docs/USING_METHODS.md', $doc);

    \array_map(unlink::class, \glob('src/danog/MadelineProto/*.tl'));
    foreach (['TL_mtproto_v1', "TL_telegram_v$layer", 'TL_secret', 'TL_botAPI'] as $schema) {
        \copy("schemas/$schema.tl", "src/danog/MadelineProto/$schema.tl");
    }

    $doc = \file_get_contents('src/danog/MadelineProto/MTProto.php');
    \preg_match("/'layer' => (\d+)/", $doc, $matches);
    $prevLayer = (int) $matches[1];

    if ($prevLayer === $layer) {
        return;
    }
    \preg_match_all("/const V = (\d+)/", $doc, $matches);
    $prevVersion = $matches[1][1];
    $version = $prevVersion + 1;

    $doc = \str_replace(
        [
            "'layer' => $prevLayer",
            "TL_telegram_$prevLayer",
            "const V = $prevVersion"
        ],
        [
            "'layer' => $layer",
            "TL_telegram_$layer",
            "const V = $version"
        ],
        $doc
    );
    \file_put_contents('src/danog/MadelineProto/MTProto.php', $doc);
}
