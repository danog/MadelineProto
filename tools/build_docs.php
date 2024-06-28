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
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\EventHandler\Update;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RPCError\CallAlreadyAcceptedError;
use danog\MadelineProto\RPCError\CallAlreadyDeclinedError;
use danog\MadelineProto\RPCError\ChannelPrivateError;
use danog\MadelineProto\RPCError\ChatWriteForbiddenError;
use danog\MadelineProto\RPCError\DcIdInvalidError;
use danog\MadelineProto\RPCError\EncryptionAlreadyAcceptedError;
use danog\MadelineProto\RPCError\EncryptionAlreadyDeclinedError;
use danog\MadelineProto\RPCError\FileTokenInvalidError;
use danog\MadelineProto\RPCError\InputUserDeactivatedError;
use danog\MadelineProto\RPCError\MsgIdInvalidError;
use danog\MadelineProto\RPCError\PasswordHashInvalidError;
use danog\MadelineProto\RPCError\PeerIdInvalidError;
use danog\MadelineProto\RPCError\UserIsBlockedError;
use danog\MadelineProto\RPCError\UserIsBotError;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\StrTools;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\Tools;
use danog\PhpDoc\PhpDoc;
use danog\PhpDoc\PhpDoc\MethodDoc;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

use function Amp\File\read;

chdir($d=__DIR__.'/..');

`git checkout src/InternalDoc.php`;

require 'vendor/autoload.php';

copy('https://rpc.madelineproto.xyz/v3.json', 'src/v3.json');

`rm -r src/RPCError/*`;
`git checkout src/RPCError/FloodWaitError.php`;
`git checkout src/RPCError/TimeoutError.php`;
`git checkout src/RPCError/FloodPremiumWaitError.php`;
`git checkout src/RPCError/RateLimitError.php`;

$map = [];

$whitelist = [
    EncryptionAlreadyAcceptedError::class => true,
    EncryptionAlreadyDeclinedError::class => true,
    CallAlreadyAcceptedError::class => true,
    CallAlreadyDeclinedError::class => true,
    PasswordHashInvalidError::class => true,
    MsgIdInvalidError::class => true,
    DcIdInvalidError::class => true,
    ChannelPrivateError::class => true,
    ChatWriteForbiddenError::class => true,
    InputUserDeactivatedError::class => true,
    PeerIdInvalidError::class => true,
    UserIsBlockedError::class => true,
    UserIsBotError::class => true,
    FileTokenInvalidError::class => true,
    \danog\MadelineProto\RPCError\RequestTokenInvalidError::class => true,
    \danog\MadelineProto\RPCError\SessionPasswordNeededError::class => true,
    \danog\MadelineProto\RPCError\ChannelInvalidError::class => true,
    \danog\MadelineProto\RPCError\ChatForbiddenError::class => true,
    \danog\MadelineProto\RPCError\UsernameInvalidError::class => true,
    \danog\MadelineProto\RPCError\UsernameNotOccupiedError::class => true,
];

$whitelistMethods = [
    'messages.sendMessage',
    'messages.sendMedia',
];

$year = date('Y');
$errors = json_decode(file_get_contents('https://rpc.madelineproto.xyz/v4.json'), true);
foreach ($errors['result'] as $code => $sub) {
    if (abs($code) === 500 || $code < 0) {
        continue;
    }
    $code = var_export($code, true);
    foreach ($sub as $err => $methods) {
        $err = (string) $err;
        $camel = ucfirst(StrTools::toCamelCase(strtolower($err))).'Error';
        if (!preg_match('/^\w+$/', $camel)) {
            continue;
        }
        $class = "danog\\MadelineProto\\RPCError\\$camel";
        if (array_intersect($methods, $whitelistMethods)
            && !str_contains($err, 'INVALID')
            && !str_contains($err, 'TOO_LONG')
            && !str_contains($err, '_EMPTY')
        ) {
            $whitelist[$class] = true;
        }

        $human = $humanOrig = $errors['human_result'][$err];
        $err = var_export($err, true);
        $human = var_export($human, true);

        $map[$err] = [$human, $code, $class];
        if (!isset($whitelist[$class])) {
            continue;
        }
        $phpCode = <<< PHP
            <?php declare(strict_types=1);
            /**
             * $camel error.
             *
             * This file is part of MadelineProto.
             * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
             * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
             * See the GNU Affero General Public License for more details.
             * You should have received a copy of the GNU General Public License along with MadelineProto.
             * If not, see <http://www.gnu.org/licenses/>.
             *
             * @author    Daniil Gentili <daniil@daniil.it>
             * @copyright 2016-$year Daniil Gentili <daniil@daniil.it>
             * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
             * @link https://docs.madelineproto.xyz MadelineProto documentation
             */

            namespace danog\MadelineProto\RPCError;

            use danog\MadelineProto\RPCErrorException;

            /**
             * $humanOrig
             * 
             * Note: this exception is part of the raw API, and thus is not covered by the backwards-compatibility promise.
             * 
             * Always check the changelog when upgrading, and use tools like Psalm to easily upgrade your code.
             */
            final class $camel extends RPCErrorException
            {
                protected function __construct(int \$code, string \$caller, ?\\Exception \$previous = null) {
                    parent::__construct($err, $human, \$code, \$caller, \$previous);
                }
            }
            PHP;
        file_put_contents("src/RPCError/$camel.php", $phpCode);
    }
}

$err = file_get_contents('src/RPCErrorException.php');
$err = preg_replace_callback('|// Start match.*// End match|sim', static function ($matches) use ($map, $whitelist) {
    $data = "return match (\$rpc) {\n";
    foreach ($map as $err => [$human, $code, $class]) {
        if (isset($whitelist[$class])) {
            $data .= "$err => new \\$class(\$code, \$caller, \$previous),\n";
        } else {
            $data .= "$err => new self(\$rpc, $human, \$code, \$caller, \$previous),\n";
        }
    }
    $data .= "default => new self(\$rpc, \$msg, \$code, \$caller, \$previous)\n";
    $data .= "};\n";
    return "// Start match\n$data\n// End match";
}, $err);
file_put_contents('src/RPCErrorException.php', $err);

require 'tools/translator.php';

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
        'TL'   => (new TLSchema)->setMTProtoSchema('')->setAPISchema("$d/schemas/TL_telegram_v$layer.tl")->setSecretSchema("$d/schemas/TL_secret.tl"),
        'title'       => "MadelineProto API documentation (layer $layer)",
        'description' => "MadelineProto API documentation (layer $layer)",
        'output_dir'  => "$d/docs/docs/API_docs",
        'template'    => "$d/docs/template",
        'readme'      => false,
    ],
];

$logger->logger('Creating annotations...', Logger::NOTICE);
$doc = new \danog\MadelineProto\AnnotationsBuilder(
    $logger,
    $docs[0],
    [
        'API' => API::class,
        'MTProto' => MTProto::class,
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
    'METRICS',
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
    'UPGRADING',
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
    $key = array_search($base, $order, true);
    if ($key !== false) {
        $orderedfiles[$key] = $file;
    }
}
ksort($orderedfiles);

/** @internal */
function getSummary(string $phpdoc): string
{
    $lexer = new Lexer();
    $constExprParser = new ConstExprParser();
    $typeParser = new TypeParser($constExprParser);
    $parser = new PhpDocParser(
        $typeParser,
        $constExprParser,
        textBetweenTagsBelongsToDescription: true
    );
    foreach ($parser->parse(new TokenIterator($lexer->tokenize($phpdoc)))->children as $t) {
        if ($t instanceof PhpDocTextNode) {
            return explode("\n", $t->text)[0];
        }
    }
    return '';
}

/** @internal */
function printTypes(array $types, string $type, string $indent = ''): string
{
    $phpdoc = PhpDoc::fromNamespace();
    $data = '';
    foreach ($types as $class) {
        if ($type === 'concretefilters' && $class === Update::class) {
            continue;
        }
        $refl = new ReflectionClass($class);
        $link = "https://docs.madelineproto.xyz/PHP/".str_replace('\\', '/', $class).'.html';
        if (!$refl->getDocComment()) {
            throw new AssertionError("No documentation for $class!");
        }
        $f = getSummary($refl->getDocComment());
        if ($refl->hasMethod('__construct')) {
            $c = $refl->getMethod('__construct');
            if ($c->getParameters() && $type === 'attributefilters') {
                $c = new MethodDoc($phpdoc, $c);
                $c = $c->getSignature();
                $class .= str_replace(['__construct', '\danog\MadelineProto\EventHandler\Filter\\'], '', $c);
            }
        }
        $data .= "$indent* [$class &raquo;]($link) - $f\n";
        if ($type !== 'concretefilters') {
            continue;
        }
        $data .= "$indent  * [Full property list &raquo;]($link#properties)\n";
        $data .= "$indent  * [Full bound method list &raquo;]($link#method-list)\n";
    }
    return $data;
}

foreach ($orderedfiles as $key => $filename) {
    $lines = file_get_contents($filename);
    $lines = preg_replace_callback('/\<\!--\s+cut_here\s+(\S+)\s+-->.*\<\!--\s+cut_here_end\s+\1\s+--\>/sim', static function ($matches) {
        [, $match] = $matches;
        if ($match === "concretefilters") {
            $result = ClassFinder::getClassesInNamespace(
                \danog\MadelineProto::class,
                ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_ALL
            );
            $result []= ServiceMessage::class;
            $result = array_filter(
                $result,
                static fn ($class) => is_subclass_of($class, Update::class)
            );
            sort($result);
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
            $result = array_filter($result, static fn (string $class) => (new ReflectionClass($class))->getAttributes());
            $data = printTypes($result, $match);
        } elseif ($match === "plugins") {
            $result = ClassFinder::getClassesInNamespace(
                \danog\MadelineProto\EventHandler\Plugin::class,
                ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_ALL
            );
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
    $index .= "* [$title]($file) - $description\n";
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
            if (basename($filename) === 'UPDATES.md' && str_contains($url, 'Broadcast/Progress')) {
                $result = ClassFinder::getClassesInNamespace(
                    \danog\MadelineProto::class,
                    ClassFinder::RECURSIVE_MODE | ClassFinder::ALLOW_ALL
                );
                $result []= ServiceMessage::class;
                $result = array_filter(
                    $result,
                    static fn ($class) => is_subclass_of($class, Update::class)
                );
                sort($result);
                $data = printTypes($result, $match, "  ");
                $index .= $data;
                break;
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
description: Async PHP client API for the telegram MTProto protocol
nav_order: 1
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
'.$readme);

include 'phpdoc.php';
