<?php declare(strict_types=1);

use danog\MadelineProto\FileRefExtractor\BuildMode\Ast;
use danog\MadelineProto\FileRefExtractor\FileRefGenerator;
use danog\MadelineProto\TL\TL;

require 'vendor/autoload.php';

// Cleanup schema
$schemaFile = __DIR__.'/../src/TL_file_ref_map_schema.tl';
$schema = explode("\n", file_get_contents($schemaFile));
foreach ($schema as &$line) {
    $line = rtrim(trim($line), ';');
    if (str_starts_with($line, '//') || !$line) {
        continue;
    }
    $line = explode(" ", $line, 2);
    $line[0] = preg_replace('/#.*/', '', $line[0]);
    $line = implode(" ", $line);
    $id = Ast::crc($line);

    $line = explode(" ", $line, 2);
    $line[0] .= "#$id";
    $line = implode(" ", $line);
    $line .= ';';
}
$schema = implode("\n", $schema);
file_put_contents($schemaFile, $schema);

// Gen ref files

$list = __DIR__.'/../schemas/list.json';
$list = file_get_contents($list);
$list = json_decode($list, true);
$last = end($list);

function generate(int $layer, string $schema): void
{
    FileRefGenerator::generate(
        $layer,
        __DIR__."/../schemas/TL_telegram_v$layer.tl",
        __DIR__.'/../src/file_ref_map.dat',
        __DIR__.'/../src/file_ref_map.json',
    );

    copy(
        __DIR__."/../src/TL_file_ref_map_schema.tl",
        __DIR__."/../schemas/TL_telegram_v{$layer}_file_ref_map_schema.tl"
    );

    $TL = new TL(null);
    $f = json_encode($TL->toJson($schema), flags: JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
    file_put_contents(__DIR__."/../schemas/TL_telegram_v{$layer}_file_ref_map_schema.json", $f);
    file_put_contents(__DIR__."/../src/TL_file_ref_map_schema.json", $f);

    copy(
        __DIR__."/../src/file_ref_map.dat",
        __DIR__."/../schemas/TL_telegram_v{$layer}_file_ref_map.dat"
    );
    copy(
        __DIR__."/../src/file_ref_map.json",
        __DIR__."/../schemas/TL_telegram_v{$layer}_file_ref_map.json"
    );
}

if (isset($argv[1])) {
    if (count($argv) < 5) {
        die("Usage:\n{$argv[0]} <layer> <inputSchema> <output> <outputJson>\nOR\n{$argv[0]} (no args, uses schemas in schemas folder)\n");
    }

    FileRefGenerator::generate(
        (int) $argv[1],
        $argv[2],
        $argv[3],
        $argv[4],
    );
    die;
}

$res = [];
foreach (glob(getcwd().'/schemas/TL_telegram_*_file_ref_map.json') as $file) {
    preg_match("/telegram_v(\d+)/", $file, $matches);
    $res[$matches[1]] = true;
}
ksort($res);

$start = min(214, array_key_first($res));
$end = max(array_key_last($res), $last);
/*
$res = [];
$start = 214;
*/

for ($layer = $start; $layer <= $end; $layer++) {
    if (!isset($res[$layer])) {
        generate($layer, $schema);
        $res[$layer] = true;
    }
}
ksort($res);

generate($last, $schema);

file_put_contents(getcwd().'/schemas/list_file_ref_map.json', json_encode(array_keys($res)));
