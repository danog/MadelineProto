#!/usr/bin/env php
<?php
/*
Copyright 2016-2017 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/
require 'vendor/autoload.php';
$param = 1;
\danog\MadelineProto\Logger::constructor($param);
set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);

\danog\MadelineProto\Logger::log(['Copying readme...'], \danog\MadelineProto\Logger::NOTICE);

file_put_contents('docs/index.md', '---
title: MadelineProto documentation
description: PHP implementation of telegram\'s MTProto protocol
---
'.str_replace('<img', '<amp-img', file_get_contents('README.md')));

$docs = [
    [
        'tl_schema'   => ['td' => __DIR__.'/src/danog/MadelineProto/TL_td.tl'],
        'title'       => 'MadelineProto API documentation (td-lib)',
        'description' => 'MadelineProto API documentation (td-lib)',
        'output_dir'  => __DIR__.'/docs/TD_docs',
        'readme'      => false,
        'td'          => true,
    ],
    [
        'tl_schema'   => ['mtproto' => __DIR__.'/src/danog/MadelineProto/TL_mtproto_v1.json'],
        'title'       => 'MadelineProto API documentation (mtproto)',
        'description' => 'MadelineProto API documentation (mtproto)',
        'output_dir'  => __DIR__.'/docs/MTProto_docs',
        'readme'      => false,
    ],
    [
        'tl_schema'   => ['telegram' => __DIR__.'/src/danog/MadelineProto/TL_telegram_v71.tl', 'calls' => __DIR__.'/src/danog/MadelineProto/TL_calls.tl', 'secret' => __DIR__.'/src/danog/MadelineProto/TL_secret.tl', 'td' => __DIR__.'/src/danog/MadelineProto/TL_td.tl'],
        'title'       => 'MadelineProto API documentation (layer 71)',
        'description' => 'MadelineProto API documentation (layer 71)',
        'output_dir'  => __DIR__.'/docs/API_docs',
        'readme'      => false,
    ],
];

$layer_list = '';
foreach (array_slice(glob(__DIR__.'/src/danog/MadelineProto/TL_telegram_*'), 0, -1) as $file) {
    $layer = preg_replace(['/.*telegram_/', '/\..+/'], '', $file);
    $docs[] = [
        'tl_schema'   => ['telegram' => $file],
        'title'       => 'MadelineProto API documentation (layer '.$layer.')',
        'description' => 'MadelineProto API documentation (layer '.$layer.')',
        'output_dir'  => __DIR__.'/old_docs/API_docs_'.$layer,
        'readme'      => true,
    ];
    $layer_list = '[Layer '.$layer.'](API_docs_'.$layer.'/)  
';
}

file_put_contents('old_docs/README.md', '---
title: Documentations of old mtproto layers
description: Documentation of old mtproto layers
---
# Documentation of old mtproto layers  

'.$layer_list);

$doc = new \danog\MadelineProto\AnnotationsBuilder($docs[2]);
$doc->mk_annotations();

foreach ($docs as $settings) {
    $doc = new \danog\MadelineProto\DocsBuilder($settings);
    $doc->mk_docs();
}
