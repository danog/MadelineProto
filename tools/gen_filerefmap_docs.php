<?php declare(strict_types=1);

/**
 * Regenerates the generated blocks of the file reference documentation (https://core.telegram.org/api/file-references)
 * from the file reference map.
 *
 * Usage: php tools/gen_filerefmap_docs.php DOCS_DIR [MAP_JSON] [MAP_SCHEMA_TL]
 *
 * DOCS_DIR is the directory containing file-references.md and file-references/*.md (docs/api in the core docs):
 * only the content between the <!-- start NAME --> and <!-- end NAME --> markers of these files is rewritten, in place.
 */

use danog\MadelineProto\FileRefExtractor\DocsRenderer;

require __DIR__.'/../vendor/autoload.php';

if ($argc < 2 || $argc > 4) {
    fwrite(STDERR, "Usage: php {$argv[0]} DOCS_DIR [MAP_JSON] [MAP_SCHEMA_TL]".PHP_EOL);
    exit(1);
}

$docs = rtrim($argv[1], '/');
$map = json_decode(file_get_contents($argv[2] ?? __DIR__.'/../src/file_ref_map.json'), true, flags: JSON_THROW_ON_ERROR);
$schema = file_get_contents($argv[3] ?? __DIR__.'/../src/TL_file_ref_map_schema.tl');

$renderer = new DocsRenderer($map, $schema);

if (!file_exists("$docs/file-references.md")) {
    throw new RuntimeException("$docs/file-references.md does not exist");
}
foreach (["$docs/file-references.md", ...glob("$docs/file-references/*.md")] as $page) {
    $old = file_get_contents($page);
    $new = $renderer->renderPage($old);
    if ($old !== $new) {
        file_put_contents($page, $new);
        echo "Updated $page".PHP_EOL;
    }
}
