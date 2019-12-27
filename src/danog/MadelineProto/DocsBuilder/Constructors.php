<?php

/**
 * Constructors module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\DocsBuilder;

use danog\MadelineProto\Tools;

trait Constructors
{
    public function mkConstructors()
    {
        foreach (\glob('constructors/'.$this->any) as $unlink) {
            \unlink($unlink);
        }
        if (\file_exists('constructors')) {
            \rmdir('constructors');
        }
        \mkdir('constructors');
        $this->docs_constructors = [];
        $this->logger->logger('Generating constructors documentation...', \danog\MadelineProto\Logger::NOTICE);
        $got = [];
        foreach ($this->TL->getConstructors($this->td)->by_predicate_and_layer as $predicate => $id) {
            $data = $this->TL->getConstructors($this->td)->by_id[$id];
            if (isset($got[$id])) {
                $data['layer'] = '';
            }
            $got[$id] = '';
            /*
                        if (preg_match('/%/', $type)) {
                            $type = $this->TL->getConstructors($this->td)->findByType(str_replace('%', '', $type))['predicate'];
                        }*/
            $layer = isset($data['layer']) && $data['layer'] !== '' ? '_'.$data['layer'] : '';
            $type = \str_replace(['<', '>'], ['_of_', ''], $data['type']);
            $php_type = \preg_replace('/.*_of_/', '', $type);
            $constructor = \str_replace(['<', '>'], ['_of_', ''], $data['predicate']);
            $php_constructor = \preg_replace('/.*_of_/', '', $constructor);
            if (!isset($this->types[$php_type])) {
                $this->types[$php_type] = ['constructors' => [], 'methods' => []];
            }
            if (!\in_array($data, $this->types[$php_type]['constructors'])) {
                $this->types[$php_type]['constructors'][] = $data;
            }
            $params = '';
            foreach ($data['params'] as $param) {
                if (\in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($type === 'EncryptedMessage' && $param['name'] === 'bytes' && !isset($this->settings['td'])) {
                    $param['name'] = 'decrypted_message';
                    $param['type'] = 'DecryptedMessage';
                }
                $type_or_subtype = isset($param['subtype']) ? 'subtype' : 'type';
                $type_or_bare_type = \ctype_upper(Tools::end(\explode('.', $param[$type_or_subtype]))[0]) || \in_array($param[$type_or_subtype], ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int53', 'int', 'long', 'int128', 'int256', 'int512']) ? 'types' : 'constructors';
                $param[$type_or_subtype] = \str_replace(['true', 'false'], ['Bool', 'Bool'], $param[$type_or_subtype]);
                if (\preg_match('/%/', $param[$type_or_subtype])) {
                    $param[$type_or_subtype] = $this->TL->getConstructors($this->td)->findByType(\str_replace('%', '', $param[$type_or_subtype]))['predicate'];
                }
                if (\substr($param[$type_or_subtype], -1) === '>') {
                    $param[$type_or_subtype] = \substr($param[$type_or_subtype], 0, -1);
                }
                $params .= "'".$param['name']."' => ";
                $param[$type_or_subtype] = '['.Tools::markdownEscape($param[$type_or_subtype]).'](../'.$type_or_bare_type.'/'.$param[$type_or_subtype].'.md)';
                $params .= (isset($param['subtype']) ? '\\['.$param[$type_or_subtype].'\\]' : $param[$type_or_subtype]).', ';
            }
            $md_constructor = \str_replace('_', '\\_', $constructor.$layer);
            $this->docs_constructors[$constructor] = '[$'.$md_constructor.'](../constructors/'.$php_constructor.$layer.'.md) = \\['.$params.'\\];<a name="'.$constructor.$layer.'"></a>  

';
            $table = empty($data['params']) ? '' : '### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
';
            if (!isset($this->TL->getDescriptions()['constructors'][$data['predicate']])) {
                $this->addToLang('object_'.$data['predicate']);
                if (\danog\MadelineProto\Lang::$lang['en']['object_'.$data['predicate']] !== '') {
                    $this->TL->getDescriptions()['constructors'][$data['predicate']]['description'] = \danog\MadelineProto\Lang::$lang['en']['object_'.$data['predicate']];
                }
            }

            if (isset($this->TL->getDescriptions()['constructors'][$data['predicate']]) && !empty($data['params'])) {
                $table = '### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
';
            }
            $params = '';
            $lua_params = '';
            $pwr_params = '';
            $hasreplymarkup = false;
            $hasentities = false;
            foreach ($data['params'] as $param) {
                if (\in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($type === 'EncryptedMessage' && $param['name'] === 'bytes' && !isset($this->settings['td'])) {
                    $param['name'] = 'decrypted_message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($type === 'DecryptedMessageMedia' && \in_array($param['name'], ['key', 'iv'])) {
                    unset(\danog\MadelineProto\Lang::$lang['en']['object_'.$data['predicate'].'_param_'.$param['name'].'_type_'.$param['type']]);
                    continue;
                }
                $ptype = $param[isset($param['subtype']) ? 'subtype' : 'type'];
                //$type_or_bare_type = 'types';
                /*if (isset($param['subtype'])) {
                      if ($param['type'] === 'vector') {
                          $type_or_bare_type = 'constructors';
                      }
                  }*/
                if (\preg_match('/%/', $ptype)) {
                    $ptype = $this->TL->getConstructors($this->td)->findByType(\str_replace('%', '', $ptype))['predicate'];
                }
                $type_or_bare_type = (\ctype_upper(Tools::end(\explode('_', $ptype))[0]) || \in_array($ptype, ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int53', 'int', 'long', 'int128', 'int256', 'int512'])) && $ptype !== 'MTmessage' ? 'types' : 'constructors';
                if (\substr($ptype, -1) === '>') {
                    $ptype = \substr($ptype, 0, -1);
                }
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                $human_ptype = $ptype;
                if (\strpos($type, 'Input') === 0 && \in_array($ptype, ['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputDialogPeer', 'DialogPeer', 'NotifyPeer', 'InputNotifyPeer', 'InputPeer']) && !isset($this->settings['td'])) {
                    $human_ptype = 'Username, chat ID, Update, Message or '.$ptype;
                }
                if (\strpos($type, 'Input') === 0 && \in_array($ptype, ['InputMedia', 'InputDocument', 'InputPhoto']) && !isset($this->settings['td'])) {
                    $human_ptype = 'MessageMedia, Message, Update or '.$ptype;
                }
                if (\in_array($ptype, ['InputMessage']) && !isset($this->settings['td'])) {
                    $human_ptype = 'Message ID or '.$ptype;
                }
                if (\in_array($ptype, ['InputEncryptedChat']) && !isset($this->settings['td'])) {
                    $human_ptype = 'Secret chat ID, Update, EncryptedMessage or '.$ptype;
                }
                if (\in_array($ptype, ['InputFile']) && !isset($this->settings['td'])) {
                    $human_ptype = 'File path or '.$ptype;
                }
                if (\in_array($ptype, ['InputEncryptedFile']) && !isset($this->settings['td'])) {
                    $human_ptype = 'File path or '.$ptype;
                }
                $table .= '|'.\str_replace('_', '\\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.\str_replace('_', '\\_', $human_ptype).'](../'.$type_or_bare_type.'/'.$ptype.'.md) | '.(isset($param['pow']) || $this->TL->getConstructors($this->td)->findByPredicate(\lcfirst($param['type']).'Empty') || ($data['type'] === 'InputMedia' && $param['name'] === 'mime_type') || ($data['type'] === 'DocumentAttribute' && \in_array($param['name'], ['w', 'h', 'duration'])) ? 'Optional' : 'Yes').'|';

                if (!isset($this->TL->getDescriptions()['constructors'][$data['predicate']]['params'][$param['name']])) {
                    $this->addToLang('object_'.$data['predicate'].'_param_'.$param['name'].'_type_'.$param['type']);
                    if (isset($this->TL->getDescriptions()['constructors'][$data['predicate']]['description'])) {
                        $this->TL->getDescriptions()['constructors'][$data['predicate']]['params'][$param['name']] = \danog\MadelineProto\Lang::$lang['en']['object_'.$data['predicate'].'_param_'.$param['name'].'_type_'.$param['type']];
                    }
                }
                if (isset($this->TL->getDescriptions()['constructors'][$data['predicate']]['params'][$param['name']])) {
                    $table .= $this->TL->getDescriptions()['constructors'][$data['predicate']]['params'][$param['name']].'|';
                }
                $table .= PHP_EOL;
                $pptype = \in_array($ptype, ['string', 'bytes']) ? "'".$ptype."'" : $ptype;
                $ppptype = \in_array($ptype, ['string']) ? '"'.$ptype.'"' : $ptype;
                $ppptype = \in_array($ptype, ['bytes']) ? '{"_": "bytes", "bytes":"base64 encoded '.$ptype.'"}' : $ppptype;
                $params .= ", '".$param['name']."' => ";
                $params .= isset($param['subtype']) ? '['.$pptype.', '.$pptype.']' : $pptype;
                $lua_params .= ', '.$param['name'].'=';
                $lua_params .= isset($param['subtype']) ? '{'.$pptype.'}' : $pptype;
                $pwr_params .= ', "'.$param['name'].'": '.(isset($param['subtype']) ? '['.$ppptype.']' : $ppptype);
                if ($param['name'] === 'reply_markup') {
                    $hasreplymarkup = true;
                }
            }
            $params = "['_' => '".$data['predicate']."'".$params.']';
            $lua_params = "{_='".$data['predicate']."'".$lua_params.'}';
            $pwr_params = '{"_": "'.$data['predicate'].'"'.$pwr_params.'}';
            $description = isset($this->TL->getDescriptions()['constructors'][$data['predicate']]) ? $this->TL->getDescriptions()['constructors'][$data['predicate']]['description'] : $constructor.' attributes, type and example';
            $symFile = \str_replace('.', '_', $constructor.$layer);
            $redir = $symFile !== $constructor.$layer ? "\nredirect_from: /API_docs/constructors/$symFile.html" : '';
            $header = '---
title: '.$data['predicate'].'
description: '.$description.'
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png'.$redir.'
---
# Constructor: '.\str_replace('_', '\\_', $data['predicate'].$layer).'  
[Back to constructors index](index.md)



';
            $table .= '


';
            if (isset($this->TL->getDescriptions()['constructors'][$data['predicate']])) {
                $header .= $this->TL->getDescriptions()['constructors'][$data['predicate']]['description'].PHP_EOL.PHP_EOL;
            }
            $type = '### Type: ['.\str_replace('_', '\\_', $php_type).'](../types/'.$php_type.'.md)


';
            $example = '';
            if (!isset($this->settings['td'])) {
                $example = '### Example:

```php
$'.$constructor.$layer.' = '.$params.';
```  


Or, if you\'re into Lua:

```lua
'.$constructor.$layer.'='.$lua_params.'

```


';
                if ($hasreplymarkup) {
                    $example .= '
## Usage of reply_markup

You can provide bot API reply_markup objects here.  


';
                }
                if ($hasentities) {
                    $example .= '
## Usage of parseMode:

Set parseMode to html to enable HTML parsing of the message.  

Set parseMode to Markdown to enable markown AND html parsing of the message.  

The following tags are currently supported:

```html
<br>a newline
<b><i>bold works ok, internal tags are stripped</i> </b>
<strong>bold</strong>
<em>italic</em>
<i>italic</i>
<code>inline fixed-width code</code>
<pre>pre-formatted fixed-width code block</pre>
<a href="https://github.com">URL</a>
<a href="mention:@danogentili">Mention by username</a>
<a href="mention:186785362">Mention by user id</a>
<pre language="json">Pre tags can have a language attribute</pre>
```

You can also use normal markdown, note that to create mentions you must use the `mention:` syntax like in html:  

```markdown
[Mention by username](mention:@danogentili)
[Mention by user id](mention:186785362)
```

MadelineProto supports all html entities supported by [html_entity_decode](http://php.net/manual/en/function.html-entity-decode.php).
';
                }
            }
            \file_put_contents('constructors/'.$constructor.$layer.'.md', $header.$table.$type.$example);
        }
        $this->logger->logger('Generating constructors index...', \danog\MadelineProto\Logger::NOTICE);
        \ksort($this->docs_constructors);
        $last_namespace = '';
        foreach ($this->docs_constructors as $constructor => &$value) {
            $new_namespace = \preg_replace('/_.*/', '', $constructor);
            $br = $new_namespace != $last_namespace ? '***
<br><br>' : '';
            $value = $br.$value;
            $last_namespace = $new_namespace;
        }
        \file_put_contents('constructors/'.$this->index, '---
title: Constructors
description: List of constructors
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# Constructors  
[Back to API documentation index](..)

'.\implode('', $this->docs_constructors));
    }
}
