#!/usr/bin/env php
<?php
/**
 * Copyright 2016-2019 Daniil Gentili
 * (https://daniil.it)
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 */

use danog\MadelineProto\API;
use danog\MadelineProto\APIFactory;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TON\API as TONAPI;
use danog\MadelineProto\TON\APIFactory as TONAPIFactory;
use danog\MadelineProto\TON\Lite;

\chdir($d=__DIR__.'/..');

require 'vendor/autoload.php';

$param = 1;
\danog\MadelineProto\Magic::classExists();
\danog\MadelineProto\Logger::constructor($param);
$logger = \danog\MadelineProto\Logger::$default;

\set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);

\danog\MadelineProto\Logger::log('Copying readme...', \danog\MadelineProto\Logger::NOTICE);

\file_put_contents('docs/docs/index.md', '---
title: MadelineProto documentation
description: PHP client/server for the telegram MTProto protocol (a better tg-cli)
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
'.\str_replace('<img', '<amp-img', \file_get_contents('README.md')));

$docs = [
/*    [
        'tl_schema'   => ['td' => "$d/src/danog/MadelineProto/TL_td.tl"],
        'title'       => 'MadelineProto API documentation (td-lib)',
        'description' => 'MadelineProto API documentation (td-lib)',
        'output_dir'  => "$d/docs/docs/TD_docs",
        'readme'      => false,
        'td'          => true,
    ],*/
    [
        'tl_schema'   => ['mtproto' => "$d/src/danog/MadelineProto/TL_mtproto_v1.json"],
        'title'       => 'MadelineProto API documentation (mtproto)',
        'description' => 'MadelineProto API documentation (mtproto)',
        'output_dir'  => "$d/docs/docs/MTProto_docs",
        'readme'      => false,
    ],
    [
        'tl_schema'   => ['telegram' => "$d/src/danog/MadelineProto/TL_telegram_v105.tl", 'calls' => "$d/src/danog/MadelineProto/TL_calls.tl", 'secret' => "$d/src/danog/MadelineProto/TL_secret.tl", 'td' => "$d/src/danog/MadelineProto/TL_td.tl"],
        'title'       => 'MadelineProto API documentation (layer 105)',
        'description' => 'MadelineProto API documentation (layer 105)',
        'output_dir'  => "$d/docs/docs/API_docs",
        'readme'      => false,
    ],
];

$layer_list = '';
foreach (\array_slice(\glob("$d/src/danog/MadelineProto/TL_telegram_*"), 0, -1) as $file) {
    $layer = \preg_replace(['/.*telegram_/', '/\..+/'], '', $file);
    $docs[] = [
        'tl_schema'   => ['telegram' => $file],
        'title'       => 'MadelineProto API documentation (layer '.$layer.')',
        'description' => 'MadelineProto API documentation (layer '.$layer.')',
        'output_dir'  => "$d/docs/old_docs/API_docs_".$layer,
        'readme'      => true,
    ];
    $layer_list = '[Layer '.$layer.'](API_docs_'.$layer.'/)  
';
}

\file_put_contents('docs/old_docs/README.md', '---
title: Documentations of old mtproto layers
description: Documentation of old mtproto layers
---
# Documentation of old mtproto layers  

'.$layer_list);

$doc = new \danog\MadelineProto\AnnotationsBuilder(
    $logger,
    $docs[1],
    \dirname(__FILE__).'/../src/danog/MadelineProto/InternalDoc.php',
    [
        'API' => API::class,
        'APIFactory' => APIFactory::class,
        'MTProto' => MTProto::class
    ],
    'danog\\MadelineProto'
);
$doc->mkAnnotations();

$ton = [
    'tl_schema' => [
        'lite_api' => "$d/src/danog/MadelineProto/TON/schemes/lite_api.tl",
        'ton_api' => "$d/src/danog/MadelineProto/TON/schemes/ton_api.tl",
        //'tonlib_api' => "$d/src/danog/MadelineProto/TON/schemes/tonlib_api.tl",
    ]
];

$doc = new \danog\MadelineProto\AnnotationsBuilder(
    $logger,
    $ton,
    \dirname(__FILE__).'/../src/danog/MadelineProto/TON/InternalDoc.php',
    [
        'API' => TONAPI::class,
        'APIFactory' => TONAPIFactory::class,
        'MTProto' => Lite::class
    ],
    'danog\\MadelineProto\\TON'
);
$doc->mkAnnotations();
foreach ($docs as $settings) {
    $doc = new \danog\MadelineProto\DocsBuilder($logger, $settings);
    $doc->mkDocs();
}

\chdir(__DIR__.'/..');

$orderedfiles = [];
$order = [
    'CREATING_A_CLIENT',
    'LOGIN',
    'FEATURES',
    'REQUIREMENTS',
    'INSTALLATION',
    'UPDATES',
    'SETTINGS',
    'SELF',
    'EXCEPTIONS',
    'FLOOD_WAIT',
    'LOGGING',
    'CALLS',
    'FILES',
    'CHAT_INFO',
    'DIALOGS',
    'INLINE_BUTTONS',
    'SECRET_CHATS',
    'LUA',
    'PROXY',
    'ASYNC',
    'USING_METHODS',
    'CONTRIB',
    'TEMPLATES',
];
$index = '';
$files = \glob('docs/docs/docs/*md');
foreach ($files as $file) {
    $base = \basename($file, '.md');
    if ($base === 'UPDATES_INTERNAL') {
        continue;
    }
    $key = \array_search($base, $order);
    if ($key !== false) {
        $orderedfiles[$key] = $file;
    }
}
\ksort($orderedfiles);
foreach ($orderedfiles as $key => $filename) {
    $lines = \explode("\n", \file_get_contents($filename));
    while (\end($lines) === '' || \strpos(\end($lines), 'Next')) {
        unset($lines[\count($lines) - 1]);
    }
    if ($lines[0] === '---') {
        \array_shift($lines);
        while ($lines[0] !== '---') {
            \array_shift($lines);
        }
        \array_shift($lines);
    }
    \preg_match('|^# (.*)|', $lines[0], $matches);
    $title = $matches[1];
    $description = $lines[2];

    \array_unshift($lines, '---', 'title: '.$title, 'description: '.$description, 'image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png', '---');

    if (isset($orderedfiles[$key + 1])) {
        $nextfile = 'https://docs.madelineproto.xyz/docs/'.\basename($orderedfiles[$key + 1], '.md').'.html';
        $prevfile = $key === 0 ? 'https://docs.madelineproto.xyz' : 'https://docs.madelineproto.xyz/docs/'.\basename($orderedfiles[$key - 1], '.md').'.html';
        $lines[\count($lines)] = "\n<a href=\"$nextfile\">Next section</a>";
    } else {
        $lines[\count($lines)] = "\n<a href=\"https://docs.madelineproto.xyz/#very-complex-and-complete-examples\">Next section</a>";
    }
    \file_put_contents($filename, \implode("\n", $lines));

    $file = \file_get_contents($filename);

    \preg_match_all('|( *)\* \[(.*)\]\((.*)\)|', $file, $matches);
    $file = 'https://docs.madelineproto.xyz/docs/'.\basename($filename, '.md').'.html';
    $index .= "* [$title]($file)\n";
    if (\basename($filename) !== 'FEATURES.md') {
        foreach ($matches[1] as $key => $match) {
            $spaces = "  $match";
            $name = $matches[2][$key];
            $url = $matches[3][$key][0] === '#' ? $file.$matches[3][$key] : $matches[3][$key];
            $index .= "$spaces* [$name]($url)\n";
            if ($name === 'FULL API Documentation with descriptions') {
                $spaces .= '  ';
                \preg_match_all('|\* (.*)|', \file_get_contents('docs/docs/API_docs/methods/index.md'), $smatches);
                foreach ($smatches[1] as $key => $match) {
                    $match = \str_replace('href="', 'href="https://docs.madelineproto.xyz/API_docs/methods/', $match);
                    $index .= "$spaces* ".$match."\n";
                }
            }
        }
    }
}

$readme = \explode('## ', \file_get_contents('README.md'));
foreach ($readme as &$section) {
    if (\explode("\n", $section)[0] === 'Documentation') {
        $section = "Documentation\n\n".$index."\n";
    }
}
$readme = \implode('## ', $readme);

\file_put_contents('README.md', $readme);
\file_put_contents('docs/docs/index.md', '---
title: MadelineProto documentation
description: PHP client/server for the telegram MTProto protocol (a better tg-cli)
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
'.$readme);
