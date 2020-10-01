<?php

/**
 * Load schema file names.
 *
 * @return array
 */
function loadSchemas(): array
{
    $res = [];
    foreach (\glob(\getcwd().'/schemas/TL_telegram_*') as $file) {
        \preg_match("/telegram_v(\d+)/", $file, $matches);
        $res[$matches[1]] = $file;
    }
    \ksort($res);
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
    $schemas = \array_keys($schemas);
    return \end($schemas);
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
    $docs = [];
    $layer_list = '';
    foreach (\array_slice($layers, 0, -1) as $layer => $file) {
        $layer = "v$layer";
        $docs[] = [
            'tl_schema'   => ['telegram' => $file, 'mtproto' => '', 'secret' => '', 'td' => ''],
            'title'       => 'MadelineProto API documentation (layer '.$layer.')',
            'description' => 'MadelineProto API documentation (layer '.$layer.')',
            'output_dir'  => \getcwd()."/docs/old_docs/API_docs_".$layer,
            'template'    => \getcwd()."/docs/template",
            'readme'      => true,
        ];
        $layer_list .= "[Layer $layer](API_docs_$layer/)  \n";
    }
    \file_put_contents('docs/old_docs/README.md', '---
title: Documentation of old mtproto layers
description: Documentation of old mtproto layers
---
# Documentation of old mtproto layers  

'.$layer_list);
    return $docs;
}
