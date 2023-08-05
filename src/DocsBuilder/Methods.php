<?php

declare(strict_types=1);

/**
 * Methods module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\DocsBuilder;

use AssertionError;
use danog\MadelineProto\API;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\StrTools;
use danog\MadelineProto\Tools;
use danog\PhpDoc\PhpDoc;
use danog\PhpDoc\PhpDoc\MethodDoc;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionMethod;

use const PHP_EOL;

/**
 * @internal This garbage code needs to be thrown away completely and rewritten from scratch.
 */
trait Methods
{
    private array $docs_methods;
    private array $human_docs_methods;
    public function mkMethods(): void
    {
        static $bots;
        if (!$bots) {
            $bots = \json_decode(\file_get_contents('https://rpc.madelineproto.xyz/bot.json'), true)['result'];
        }
        static $errors;
        if (!$errors) {
            $errors = \json_decode(\file_get_contents('https://rpc.madelineproto.xyz/v4.json'), true);
        }
        $new = ['result' => []];
        foreach ($errors['result'] as $code => $suberrors) {
            foreach ($suberrors as $method => $suberrors) {
                if (!isset($new[$method])) {
                    $new[$method] = [];
                }
                foreach ($suberrors as $error) {
                    $new['result'][$method][] = [$error, $code];
                }
            }
        }
        foreach (\glob('methods/'.$this->any) as $unlink) {
            \unlink($unlink);
        }
        if (\file_exists('methods')) {
            \rmdir('methods');
        }
        \mkdir('methods');
        $this->docs_methods = [];
        $this->human_docs_methods = [];
        $this->logger->logger('Generating methods documentation...', Logger::NOTICE);
        foreach ($this->TL->getMethods()->by_id as $id => $data) {
            $method = $data['method'];
            $phpMethod = StrTools::methodEscape($method);
            $type = \str_replace(['<', '>'], ['_of_', ''], $data['type']);
            $php_type = \preg_replace('/.*_of_/', '', $type);
            if (!isset($this->types[$php_type])) {
                $this->types[$php_type] = ['methods' => [], 'constructors' => []];
            }
            if (!\in_array($data, $this->types[$php_type]['methods'], true)) {
                $this->types[$php_type]['methods'][] = $data;
            }
            $params = '';
            foreach ($data['params'] as $param) {
                if (\in_array($param['name'], ['flags', 'flags2', 'random_id', 'random_bytes'], true)) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage' && !isset($this->settings['td'])) {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $method !== 'messages.discardEncryption' && !isset($this->settings['td'])) {
                    $param['type'] = 'InputPeer';
                }
                $type_or_subtype = isset($param['subtype']) ? 'subtype' : 'type';
                $type_or_bare_type = \ctype_upper(Tools::end(\explode('.', $param[$type_or_subtype]))[0]) || \in_array($param[$type_or_subtype], ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int', 'long', 'int128', 'int256', 'int512', 'int53'], true) ? 'types' : 'constructors';
                $param[$type_or_subtype] = \str_replace(['true', 'false'], ['Bool', 'Bool'], $param[$type_or_subtype]);
                $param[$type_or_subtype] = '['.self::markdownEscape($param[$type_or_subtype]).'](/API_docs/'.$type_or_bare_type.'/'.$param[$type_or_subtype].'.md)';
                $param[$type_or_subtype] = '$'.$param[$type_or_subtype];
                $params .= $param['name'].': '.(isset($param['subtype']) ? '\\['.$param[$type_or_subtype].'\\]' : $param[$type_or_subtype]).', ';
            }
            if (!isset($this->tdDescriptions['methods'][$method])) {
                $this->addToLang('method_'.$method);
                if (Lang::$lang['en']['method_'.$method] !== '') {
                    $this->tdDescriptions['methods'][$method]['description'] = Lang::$lang['en']['method_'.$method];
                }
            }
            $md_method = '['.$phpMethod.'](/API_docs/methods/'.$method.'.md)';
            $this->docs_methods[$method] = '$MadelineProto->'.$md_method.'(\\['.$params.'\\]) === [$'.self::markdownEscape($type).'](/API_docs/types/'.$php_type.'.md)<a name="'.$method.'"></a>  

';
            $desc = StrTools::toString(\trim(\explode("\n", $this->tdDescriptions['methods'][$method]['description'] ?? '')[0], '.'));
            if ($desc !== '') {
                $desc .= ': ';
            }
            $this->human_docs_methods[$desc.$method] = '* <a href="'.$method.'.html" name="'.$method.'">'.$desc.$method.'</a>

';
            $params = '';
            $lua_params = '';
            $pwr_params = '';
            $json_params = '';
            $table = empty($data['params']) ? '' : '### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
';
            if (isset($this->tdDescriptions['methods'][$method]) && !empty($data['params'])) {
                $table = '### Parameters:

| Name     |    Type       | Description | Required |
|----------|---------------|-------------|----------|
';
            }
            $hasentities = false;
            $hasreplymarkup = false;
            $hasmessage = false;
            foreach ($data['params'] as $param) {
                if (\in_array($param['name'], ['flags', 'flags2', 'random_id', 'random_bytes'], true)) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage' && !isset($this->settings['td'])) {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $method !== 'messages.discardEncryption' && !isset($this->settings['td'])) {
                    $param['type'] = 'InputPeer';
                }
                if (!isset($this->tdDescriptions['methods'][$method]['params'][$param['name']])) {
                    if (isset($this->tdDescriptions['methods'][$method]['description'])) {
                        $this->tdDescriptions['methods'][$method]['params'][$param['name']] = Lang::$lang['en']['method_'.$method.'_param_'.$param['name'].'_type_'.$param['type']] ?? '';
                    }
                }
                if ($param['name'] === 'hash' && ($param['type'] === 'long' || $param['type'] === 'int')) {
                    $param['pow'] = 'hi';
                    $param['type'] = 'Vector t';
                    $param['subtype'] = 'long';
                }
                $ptype = $param[$type_or_subtype = isset($param['subtype']) ? 'subtype' : 'type'];
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                $human_ptype = $ptype;
                if (\in_array($ptype, ['InputDialogPeer', 'DialogPeer', 'NotifyPeer', 'InputNotifyPeer', 'User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer'], true) && !isset($this->settings['td'])) {
                    $human_ptype = 'Username, chat ID, Update, Message or '.$ptype;
                }
                if (\in_array($ptype, ['InputMedia', 'InputPhoto', 'InputDocument'], true) && !isset($this->settings['td'])) {
                    $human_ptype = 'MessageMedia, Update, Message or '.$ptype;
                }
                if (\in_array($ptype, ['InputMessage'], true) && !isset($this->settings['td'])) {
                    $human_ptype = 'Message ID or '.$ptype;
                }
                if (\in_array($ptype, ['InputEncryptedChat'], true) && !isset($this->settings['td'])) {
                    $human_ptype = 'Secret chat ID, Update, EncryptedMessage or '.$ptype;
                }
                if (\in_array($ptype, ['InputFile'], true) && !isset($this->settings['td'])) {
                    $human_ptype = 'File path or '.$ptype;
                }
                if (\in_array($ptype, ['InputEncryptedFile'], true) && !isset($this->settings['td'])) {
                    $human_ptype = 'File path or '.$ptype;
                }
                $type_or_bare_type = \ctype_upper(Tools::end(\explode('.', $param[$type_or_subtype]))[0]) || \in_array($param[$type_or_subtype], ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int', 'long', 'int128', 'int256', 'int512', 'int53'], true) ? 'types' : 'constructors';
                if (isset($this->tdDescriptions['methods'][$method])) {
                    $table .= '|'.self::markdownEscape($param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.self::markdownEscape($human_ptype).'](/API_docs/'.$type_or_bare_type.'/'.$ptype.'.md) | '.$this->tdDescriptions['methods'][$method]['params'][$param['name']].' | '.(isset($param['pow']) || $param['type'] === 'int' || $param['type'] === 'double' || ($id = $this->TL->getConstructors()->findByPredicate(\lcfirst($param['type']).'Empty')) && $id['type'] === $param['type'] || ($id = $this->TL->getConstructors()->findByPredicate('input'.$param['type'].'Empty')) && $id['type'] === $param['type'] ? 'Optional' : 'Yes').'|';
                } else {
                    $table .= '|'.self::markdownEscape($param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.self::markdownEscape($human_ptype).'](/API_docs/'.$type_or_bare_type.'/'.$ptype.'.md) | '.(isset($param['pow']) || ($param['type'] === 'long' && $param['name'] === 'hash')|| ($id = $this->TL->getConstructors()->findByPredicate(\lcfirst($param['type']).'Empty')) && $id['type'] === $param['type'] || ($id = $this->TL->getConstructors()->findByPredicate('input'.$param['type'].'Empty')) && $id['type'] === $param['type'] ? 'Optional' : 'Yes').'|';
                }
                $table .= PHP_EOL;
                $pptype = \in_array($ptype, ['string', 'bytes'], true) ? "'".$ptype."'" : '$'.$ptype;
                $ppptype = \in_array($ptype, ['string'], true) ? '"'.$ptype.'"' : $ptype;
                $ppptype = \in_array($ptype, ['bytes'], true) ? '{"_": "bytes", "bytes":"base64 encoded '.$ptype.'"}' : $ppptype;
                $params .= $param['name'].': ';
                $params .= (isset($param['subtype']) ? '['.$pptype.', '.$pptype.']' : $pptype).', ';
                $json_params .= '"'.$param['name'].'": '.(isset($param['subtype']) ? '['.$ppptype.']' : $ppptype).', ';
                $pwr_params .= $param['name'].' - Json encoded '.(isset($param['subtype']) ? ' array of '.$ptype : $ptype)."\n\n";
                $lua_params .= $param['name'].'=';
                $lua_params .= (isset($param['subtype']) ? '{'.$pptype.'}' : $pptype).', ';
                if ($param['name'] === 'reply_markup') {
                    $hasreplymarkup = true;
                }
                if ($param['name'] === 'message') {
                    $hasmessage = true;
                }
                if ($param['name'] === 'entities') {
                    $hasentities = true;
                    $table .= '|parse\\_mode| [string](/API_docs/types/string.md) | Whether to parse HTML or Markdown markup in the message| Optional |
';
                    $params .= "parse_mode: 'string', ";
                    $lua_params .= "parseMode='string', ";
                    $json_params .= '"parseMode": "string"';
                    $pwr_params = "parseMode - string\n";
                }
            }
            $description = isset($this->tdDescriptions['methods'][$method]) ? $this->tdDescriptions['methods'][$method]['description'] : $method.' parameters, return type and example';
            $symFile = \str_replace('.', '_', $method);
            $redir = $symFile !== $method ? "\nredirect_from: /API_docs/methods/{$symFile}.html" : '';
            $description = \str_replace('"', "'", \rtrim(\explode("\n", $description)[0], ':'));
            $header = $this->template('Method', $method, $description, $redir, self::markdownEscape($method));
            if ($this->td) {
                $header .= "YOU CANNOT USE THIS METHOD IN MADELINEPROTO\n\n\n\n\n";
            }
            if (\in_array($method, ['messages.getHistory', 'messages.getMessages', 'channels.getMessages'], true)) {
                $header .= "# Warning: flood wait\n**Warning: this method is prone to rate limiting with flood waits, please use the [updates event handler, instead &raquo;](/docs/UPDATES.html#async-event-driven)**\n\n";
                $header .= "# Warning: non-realtime results\n**Warning: this method is not suitable for receiving messages in real-time from chats and users, please use the [updates event handler, instead &raquo;](/docs/UPDATES.html#async-event-driven)**\n\n";
                $header .= "# Warning: this is probably NOT what you need\nYou probably need to use the [updates event handler, instead &raquo;](/docs/UPDATES.html#async-event-driven) :)\n\n";
            }
            $header .= isset($this->tdDescriptions['methods'][$method]) ? $this->tdDescriptions['methods'][$method]['description'].PHP_EOL.PHP_EOL : '';
            $table .= '

';
            $return = '### Return type: ['.self::markdownEscape($type).'](/API_docs/types/'.$php_type.'.md)

';
            $bot = !\in_array($method, $bots, true);
            $example = '';
            if (!isset($this->settings['td'])) {
                $example .= '### Can bots use this method: **'.($bot ? 'YES' : 'NO')."**\n\n\n";
                $example .= \str_replace('[]', '', $this->template('method-example', \str_replace('.', '_', $type), $phpMethod, $params, $method, $lua_params));
                if ($hasreplymarkup) {
                    $example .= $this->template('reply_markup');
                }
                if ($hasmessage) {
                    $example .= $this->template('chunks', self::markdownEscape($type), $php_type);
                }
                if ($hasentities) {
                    $example .= $this->template('parse_mode');
                }
                if (isset($new['result'][$method])) {
                    $example .= '### Errors

| Code | Type     | Description   |
|------|----------|---------------|
';
                    foreach ($new['result'][$method] as $error) {
                        [$error, $code] = $error;
                        $example .= "|{$code}|{$error}|".$errors['human_result'][$error][0].'|'."\n";
                    }
                    $example .= "\n\n";
                }
            }
            \file_put_contents('methods/'.$method.'.md', $header.$table.$return.$example);
        }
        $this->logger->logger('Generating methods index...', Logger::NOTICE);
        $reflection = new ReflectionClass(API::class);
        /** @psalm-suppress UndefinedClass */
        $phpdoc = PhpDoc::fromNamespace(\danog\MadelineProto::class);
        $phpdoc->resolveAliases();
        $builder = DocBlockFactory::createInstance();
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (\in_array(\strtolower($name), ['update2fa', 'getdialogids', 'getdialogs', 'getfulldialogs', 'getpwrchat', 'getfullinfo', 'getinfo', 'getid', 'getself', '__magic_construct', '__construct', '__destruct', '__sleep', '__wakeup'], true)) {
                continue;
            }
            $doc = $method->getDocComment();
            if (\str_contains($doc, '@internal') || \str_contains($doc, '@deprecated')) {
                continue;
            }
            if ($doc) {
                $doc = $builder->create($doc);
                $doc = \explode("\n", $doc->getSummary())[0];
            }
            if (!$doc) {
                throw new AssertionError($name);
            }
            $doc = \trim($doc, '.');
            $method = new MethodDoc($phpdoc, $method);
            $anchor = $method->getSignatureAnchor();
            $this->human_docs_methods["$doc: $name"] = '* <a href="https://docs.madelineproto.xyz/PHP/danog/MadelineProto/API.html#'.$anchor.'" name="'.$name.'">'.$doc.': '.$name.'</a>

';
        }

        \ksort($this->docs_methods);
        \ksort($this->human_docs_methods);
        $last_namespace = '';
        foreach ($this->docs_methods as $method => &$value) {
            $new_namespace = \preg_replace('/_.*/', '', $method);
            $br = $new_namespace != $last_namespace ? '***
<br><br>
' : '';
            $value = $br.$value;
            $last_namespace = $new_namespace;
        }
        \file_put_contents('methods/api_'.$this->index, $this->template('methods-api-index', $this->index, \implode('', $this->docs_methods)));
        \file_put_contents('methods/'.$this->index, $this->template('methods-index', $this->index, \implode('', $this->human_docs_methods)));
    }
}
