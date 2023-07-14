#!/usr/bin/env php
<?php
/**
 * Copyright 2016-2020 Daniil Gentili
 * (https://daniil.it)
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 */

use danog\ClassFinder\ClassFinder;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\AbstractMessage;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\Tools;
use danog\PhpDoc\PhpDoc;
use danog\PhpDoc\PhpDoc\MethodDoc;
use phpDocumentor\Reflection\DocBlockFactory;

use function Amp\File\read;

chdir($d=__DIR__.'/..');

require 'vendor/autoload.php';

require 'tools/translator.php';

copy('https://rpc.madelineproto.xyz/v3.json', 'src/v3.json');

Magic::start(light: false);
Logger::constructorFromSettings(new SettingsLogger);
$logger = Logger::$default;
set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);

$logger->logger('Merging constructor localization...', Logger::NOTICE);
mergeExtracted();

$logger->logger('Loading schemas...', Logger::NOTICE);
$schemas = loadSchemas();

$logger->logger('Upgrading layer...', Logger::NOTICE);
$layer = maxLayer($schemas);
layerUpgrade($layer);

$logger->logger("Initing docs (layer $layer)...", Logger::NOTICE);
$docs = [
    [
        'tl_schema'   => ['mtproto' => '', 'telegram' => "$d/schemas/TL_telegram_v$layer.tl", 'secret' => "$d/schemas/TL_secret.tl", 'td' => "$d/schemas/TL_td.tl"],
        'title'       => "MadelineProto API documentation (layer $layer)",
        'description' => "MadelineProto API documentation (layer $layer)",
        'output_dir'  => "$d/docs/docs/API_docs",
        'template'    => "$d/docs/template",
        'readme'      => false,
    ],
];
$docs = array_merge($docs, initDocs($schemas));

$logger->logger('Creating annotations...', Logger::NOTICE);
$doc = new \danog\MadelineProto\AnnotationsBuilder(
    $logger,
    $docs[0],
    [
        'API' => API::class,
        'MTProto' => MTProto::class
    ],
    'danog\\MadelineProto'
);
$doc->mkAnnotations();

$logger->logger('Creating docs...', Logger::NOTICE);
foreach ($docs as $settings) {
    $doc = new \danog\MadelineProto\DocsBuilder($logger, $settings);
    $doc->mkDocs();
}

chdir(__DIR__.'/..');

$logger->logger('Fixing readme...', Logger::NOTICE);
$orderedfiles = [];
$order = [
    'CREATING_A_CLIENT',
    'LOGIN',
    'FEATURES',
    'REQUIREMENTS',
    'DOCKER',
    'INSTALLATION',
    'BROADCAST',
    'UPDATES',
    'FILTERS',
    'PLUGINS',
    'DATABASE',
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
    'PROXY',
    'ASYNC',
    'FAQ',
    'USING_METHODS',
    'CONTRIB',
    'TEMPLATES',
];
$index = '';
$files = glob('docs/docs/docs/*md');
foreach ($files as $file) {
    $base = basename($file, '.md');
    if ($base === 'UPDATES_INTERNAL') {
        continue;
    }
    $key = array_search($base, $order);
    if ($key !== false) {
        $orderedfiles[$key] = $file;
    }
}
ksort($orderedfiles);

/** @internal */
function printTypes(array $types, string $type): string
{
    $b = DocBlockFactory::createInstance();
    $phpdoc = PhpDoc::fromNamespace();
    $data = '';
    foreach ($types as $class) {
        if ($type === 'concretefilters' && $class === Update::class) {
            continue;
        }
        $refl = new ReflectionClass($class);
        $link = "https://docs.madelineproto.xyz/PHP/".str_replace('\\', '/', $class).'.html';
        $f = $b->create($refl->getDocComment())->getSummary();
        if ($refl->hasMethod('__construct')) {
            $c = $refl->getMethod('__construct');
            if ($c->getParameters() && $type === 'attributefilters') {
                $c = new MethodDoc($phpdoc, $c);
                $c = $c->getSignature();
                $class .= str_replace(['__construct', '\danog\MadelineProto\EventHandler\Filter\\'], '', $c);
            }
        }
        $data .= "* [$class &raquo;]($link) - $f\n";
        if ($type !== 'concretefilters') {
            continue;
        }
        $data .= "  * [Full property list &raquo;]($link#properties)\n";
        $data .= "  * [Full bound method list &raquo;]($link#method-list)\n";
    }
    return $data;
}

foreach ($orderedfiles as $key => $filename) {
    $lines = file_get_contents($filename);
    $lines = preg_replace_callback('/\<\!--\s+cut_here\s+(\S+)\s+-->.*\<\!--\s+cut_here_end\s+\1\s+--\>/sim', function ($matches) {
        [, $match] = $matches;
        if ($match === "concretefilters") {
            $result = [Update::class, AbstractMessage::class, Message::class, ServiceMessage::class];
            $result = array_merge($result, ClassFinder::getClassesInNamespace(
                \danog\MadelineProto\EventHandler\Message::class,
                ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_ALL
            ));
            $data = printTypes($result, $match);
        } elseif ($match === "simplefilters") {
            $result = ClassFinder::getClassesInNamespace(
                \danog\MadelineProto\EventHandler\SimpleFilter::class,
                ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_ALL
            );
            $data = printTypes($result, $match);
        } elseif ($match === "attributefilters") {
            $result = ClassFinder::getClassesInNamespace(
                \danog\MadelineProto\EventHandler\Filter::class,
                ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_ALL
            );
            $result = array_filter($result, fn (string $class) => (new ReflectionClass($class))->getAttributes());
            $data = printTypes($result, $match);
        } elseif ($match === "mtprotofilters") {
            $data = " * onUpdateCustomEvent: Receives messages sent to the event handler from an API instance using the [`sendCustomEvent` &raquo;](https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#sendcustomevent-mixed-payload-void) method.\n";
            $data .= " * onAny: Catch-all filter, if defined catches all updates that aren't catched by any other filter.\n";
            $data .= " * [onUpdateBroadcastProgress &raquo;](https://docs.madelineproto.xyz/docs/BROADCAST.html#get-progress): Used to receive updates to an in-progress [message broadcast &raquo;](https://docs.madelineproto.xyz/docs/BROADCAST.html)";
            $TL = new TL(null);
            $TL->init(new TLSchema);
            foreach ($TL->getConstructors()->by_id as $cons) {
                if ($cons['type'] !== 'Update') {
                    continue;
                }
                $predicate = 'on'.ucfirst($cons['predicate']);
                $predicateRaw = $cons['predicate'];
                $desc = explode("\n", Lang::$lang['en']["object_$predicateRaw"])[0];
                $desc = str_replace(['](../', '.md'], ['](https://docs.madelineproto.xyz/API_docs/', '.html'], $desc);
                $data .= "* [$predicate &raquo;](https://docs.madelineproto.xyz/API_docs/constructors/$predicateRaw.html) - $desc\n";
            }
        } else {
            $data = read($match);
            $data = "```php\n{$data}\n```";
        }
        return "<!-- cut_here $match -->\n\n$data\n\n<!-- cut_here_end $match -->";
    }, $lines);
    $lines = explode("\n", $lines);
    while (end($lines) === '' || strpos(end($lines), 'Next')) {
        unset($lines[count($lines) - 1]);
    }
    if ($lines[0] === '---') {
        array_shift($lines);
        while ($lines[0] !== '---') {
            array_shift($lines);
        }
        array_shift($lines);
    }

    preg_match('|^# (.*)|', $lines[0], $matches);
    $title = $matches[1];
    $description = str_replace('"', "'", Tools::toString($lines[2]));

    array_unshift(
        $lines,
        '---',
        'title: "'.$title.'"',
        'description: "'.$description.'"',
        'nav_order: '.($key+4),
        'image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png',
        '---'
    );

    if (isset($orderedfiles[$key + 1])) {
        $nextfile = 'https://docs.madelineproto.xyz/docs/'.basename($orderedfiles[$key + 1], '.md').'.html';
        $lines[count($lines)] = "\n<a href=\"$nextfile\">Next section</a>";
    } else {
        $lines[count($lines)] = "\n<a href=\"https://docs.madelineproto.xyz/#very-complex-and-complete-examples\">Next section</a>";
    }
    file_put_contents($filename, implode("\n", $lines));

    $file = file_get_contents($filename);

    preg_match_all('|( *)\* \[(.*)\]\((.*)\)|', $file, $matches);
    $file = 'https://docs.madelineproto.xyz/docs/'.basename($filename, '.md').'.html';
    $index .= "* [$title]($file)\n";
    if (basename($filename) !== 'FEATURES.md') {
        foreach ($matches[1] as $key => $match) {
            $spaces = "  $match";
            $name = $matches[2][$key];
            if ($matches[3][$key][0] === '#') {
                $url = $file.$matches[3][$key];
            } elseif (substr($matches[3][$key], 0, 3) === '../') {
                $url = 'https://docs.madelineproto.xyz/'.str_replace('.md', '.html', substr($matches[3][$key], 3));
                if (basename($filename) === 'FILTERS.md') {
                    continue;
                }
            } else {
                $url = $matches[3][$key];
                if (basename($filename) === 'FILTERS.md') {
                    continue;
                }
            }
            if (basename($filename) === 'UPDATES.md' && str_starts_with($url, 'https://docs.madelineproto.xyz/PHP/danog/MadelineProto/EventHandler')) {
                continue;
            }
            $index .= "$spaces* [$name]($url)\n";
            if ($name === 'FULL API Documentation with descriptions') {
                $spaces .= '  ';
                preg_match_all('|\* (.*)|', file_get_contents('docs/docs/API_docs/methods/index.md'), $smatches);
                foreach ($smatches[1] as $key => $match) {
                    if (str_contains($match, 'You cannot use this method directly')) {
                        continue;
                    }
                    if (!str_contains($match, 'href="https://docs.madelineproto.xyz')) {
                        $match = str_replace('href="', 'href="https://docs.madelineproto.xyz/API_docs/methods/', $match);
                    }
                    $index .= "$spaces* ".$match."\n";
                }
            }
        }
    }
}

$logger->logger('Fixing readme...', Logger::NOTICE);
$readme = explode('## ', file_get_contents('README.md'));
foreach ($readme as &$section) {
    if (explode("\n", $section)[0] === 'Documentation') {
        $section = "Documentation\n\n".$index."\n";
    }
}
$readme = implode('## ', $readme);

file_put_contents('README.md', $readme);
file_put_contents('docs/docs/index.md', '---
title: MadelineProto
description: PHP client/server for the telegram MTProto protocol (a better tg-cli)
nav_order: 1
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
'.$readme);

include 'phpdoc.php';
