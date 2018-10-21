<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto\DocsBuilder;

trait Methods
{
    public function mk_methods()
    {
        $bots = json_decode(file_get_contents('https://rpc.pwrtelegram.xyz/?bot'), true)['result'];
        $errors = json_decode(file_get_contents('https://rpc.pwrtelegram.xyz/?all'), true);
        $errors['result'] = array_merge_recursive(...$errors['result']);
        foreach (glob('methods/'.$this->any) as $unlink) {
            unlink($unlink);
        }
        if (file_exists('methods')) {
            rmdir('methods');
        }
        mkdir('methods');
        $this->docs_methods = [];
        $this->human_docs_methods = [];
        $this->logger->logger('Generating methods documentation...', \danog\MadelineProto\Logger::NOTICE);
        foreach ($this->methods->by_id as $id => $data) {
            $method = str_replace('.', '_', $data['method']);
            $php_method = str_replace('.', '->', $data['method']);
            $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $data['type']);
            $php_type = preg_replace('/.*_of_/', '', $type);
            if (!isset($this->types[$php_type])) {
                $this->types[$php_type] = ['methods' => [], 'constructors' => []];
            }
            if (!in_array($data, $this->types[$php_type]['methods'])) {
                $this->types[$php_type]['methods'][] = $data;
            }
            $params = '';
            foreach ($data['params'] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage' && !isset($this->settings['td'])) {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $data['method'] !== 'messages.discardEncryption' && !isset($this->settings['td'])) {
                    $param['type'] = 'InputPeer';
                }
                $type_or_subtype = isset($param['subtype']) ? 'subtype' : 'type';
                $type_or_bare_type = ctype_upper($this->end(explode('.', $param[$type_or_subtype]))[0]) || in_array($param[$type_or_subtype], ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int', 'long', 'int128', 'int256', 'int512', 'int53']) ? 'types' : 'constructors';
                $param[$type_or_subtype] = str_replace(['.', 'true', 'false'], ['_', 'Bool', 'Bool'], $param[$type_or_subtype]);
                $param[$type_or_subtype] = '['.$this->escape($param[$type_or_subtype]).'](../'.$type_or_bare_type.'/'.$param[$type_or_subtype].'.md)';
                $params .= "'".$param['name']."' => ".(isset($param['subtype']) ? '\\['.$param[$type_or_subtype].'\\]' : $param[$type_or_subtype]).', ';
            }
            if (!isset($this->td_descriptions['methods'][$data['method']])) {
                $this->add_to_lang('method_'.$data['method']);

                if (\danog\MadelineProto\Lang::$lang['en']['method_'.$data['method']] !== '') {
                    $this->td_descriptions['methods'][$data['method']]['description'] = \danog\MadelineProto\Lang::$lang['en']['method_'.$data['method']];
                }
            }
            $md_method = '['.$php_method.']('.$method.'.md)';
            $this->docs_methods[$method] = '$MadelineProto->'.$md_method.'(\\['.$params.'\\]) === [$'.str_replace('_', '\\_', $type).'](../types/'.$php_type.'.md)<a name="'.$method.'"></a>  

';
            if (!isset(\danog\MadelineProto\MTProto::DISALLOWED_METHODS[$data['method']]) && isset($this->td_descriptions['methods'][$data['method']])) {
                $this->human_docs_methods[$this->td_descriptions['methods'][$data['method']]['description'].': '.$data['method']] = '* <a href="'.$method.'.html" name="'.$method.'">'.$this->td_descriptions['methods'][$data['method']]['description'].': '.$data['method'].'</a>  

';
            }
            $params = '';
            $lua_params = '';
            $pwr_params = '';
            $json_params = '';
            $table = empty($data['params']) ? '' : '### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
';
            if (isset($this->td_descriptions['methods'][$data['method']]) && !empty($data['params'])) {
                $table = '### Parameters:

| Name     |    Type       | Description | Required |
|----------|---------------|-------------|----------|
';
            }
            $hasentities = false;
            $hasreplymarkup = false;
            $hasmessage = false;
            foreach ($data['params'] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage' && !isset($this->settings['td'])) {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $data['method'] !== 'messages.discardEncryption' && !isset($this->settings['td'])) {
                    $param['type'] = 'InputPeer';
                }
                if ($param['name'] === 'hash' && $param['type'] === 'int') {
                    $param['pow'] = 'hi';
                    $param['type'] = 'Vector t';
                    $param['subtype'] = 'int';
                }
                $ptype = str_replace('.', '_', $param[$type_or_subtype = isset($param['subtype']) ? 'subtype' : 'type']);
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                $human_ptype = $ptype;
                if (in_array($ptype, ['InputDialogPeer', 'DialogPeer', 'NotifyPeer', 'InputNotifyPeer', 'User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer']) && !isset($this->settings['td'])) {
                    $human_ptype = 'Username, chat ID, Update, Message or '.$ptype;
                }
                if (in_array($ptype, ['InputMedia', 'InputPhoto', 'InputDocument']) && !isset($this->settings['td'])) {
                    $human_ptype = 'MessageMedia, Update, Message or '.$ptype;
                }
                if (in_array($ptype, ['InputMessage']) && !isset($this->settings['td'])) {
                    $human_ptype = 'Message ID or '.$ptype;
                }
                if (in_array($ptype, ['InputEncryptedChat']) && !isset($this->settings['td'])) {
                    $human_ptype = 'Secret chat ID, Update, EncryptedMessage or '.$ptype;
                }
                if (in_array($ptype, ['InputFile']) && !isset($this->settings['td'])) {
                    $human_ptype = 'File path or '.$ptype;
                }
                if (in_array($ptype, ['InputEncryptedFile']) && !isset($this->settings['td'])) {
                    $human_ptype = 'File path or '.$ptype;
                }
                $type_or_bare_type = ctype_upper($this->end(explode('.', $param[$type_or_subtype]))[0]) || in_array($param[$type_or_subtype], ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int', 'long', 'int128', 'int256', 'int512', 'int53']) ? 'types' : 'constructors';
                if (!isset($this->td_descriptions['methods'][$data['method']]['params'][$param['name']])) {
                    $this->add_to_lang('method_'.$data['method'].'_param_'.$param['name'].'_type_'.$param['type']);
                    if (isset($this->td_descriptions['methods'][$data['method']]['description'])) {
                        $this->td_descriptions['methods'][$data['method']]['params'][$param['name']] = \danog\MadelineProto\Lang::$lang['en']['method_'.$data['method'].'_param_'.$param['name'].'_type_'.$param['type']];
                    }
                }

                if (isset($this->td_descriptions['methods'][$data['method']])) {
                    $table .= '|'.str_replace('_', '\\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\\_', $human_ptype).'](../'.$type_or_bare_type.'/'.$ptype.'.md) | '.$this->td_descriptions['methods'][$data['method']]['params'][$param['name']].' | '.(isset($param['pow']) || (($id = $this->constructors->find_by_predicate(lcfirst($param['type']).'Empty')) && $id['type'] === $param['type']) || (($id = $this->constructors->find_by_predicate('input'.$param['type'].'Empty')) && $id['type'] === $param['type']) ? 'Optional' : 'Yes').'|';
                } else {
                    $table .= '|'.str_replace('_', '\\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\\_', $human_ptype).'](../'.$type_or_bare_type.'/'.$ptype.'.md) | '.(isset($param['pow']) || (($id = $this->constructors->find_by_predicate(lcfirst($param['type']).'Empty')) && $id['type'] === $param['type']) || (($id = $this->constructors->find_by_predicate('input'.$param['type'].'Empty')) && $id['type'] === $param['type']) ? 'Optional' : 'Yes').'|';
                }
                $table .= PHP_EOL;
                $pptype = in_array($ptype, ['string', 'bytes']) ? "'".$ptype."'" : $ptype;
                $ppptype = in_array($ptype, ['string']) ? '"'.$ptype.'"' : $ptype;
                $ppptype = in_array($ptype, ['bytes']) ? '{"_": "bytes", "bytes":"base64 encoded '.$ptype.'"}' : $ppptype;

                $params .= "'".$param['name']."' => ";
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
                    $table .= '|parse\\_mode| [string](../types/string.md) | Whether to parse HTML or Markdown markup in the message| Optional |
';
                    $params .= "'parse_mode' => 'string', ";
                    $lua_params .= "parse_mode='string', ";
                    $json_params .= '"parse_mode": "string"';
                    $pwr_params = "parse_mode - string\n";
                }
            }
            $description = isset($this->td_descriptions['methods'][$data['method']]) ? $this->td_descriptions['methods'][$data['method']]['description'] : $data['method'].' parameters, return type and example';
            $header = '---
title: '.$data['method'].'
description: '.$description.'
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# Method: '.str_replace('_', '\\_', $data['method']).'  
[Back to methods index](index.md)


';
            if (isset(\danog\MadelineProto\MTProto::DISALLOWED_METHODS[$data['method']])) {
                $header .= '**'.\danog\MadelineProto\MTProto::DISALLOWED_METHODS[$data['method']]."**\n\n\n\n\n";
                file_put_contents('methods/'.$method.'.md', $header);
                continue;
            }
            if ($this->td) {
                $header .= 'YOU CANNOT USE THIS METHOD IN MADELINEPROTO


';
            }
            $header .= isset($this->td_descriptions['methods'][$data['method']]) ? $this->td_descriptions['methods'][$data['method']]['description'].PHP_EOL.PHP_EOL : '';
            $table .= '

';
            $return = '### Return type: ['.str_replace('_', '\\_', $type).'](../types/'.$php_type.'.md)

';
            $bot = !in_array($data['method'], $bots);
            $example = '';
            if (!isset($this->settings['td'])) {
                $example .= '### Can bots use this method: **'.($bot ? 'YES' : 'NO')."**\n\n\n";
                $example .= str_replace('[]', '', '### MadelineProto Example:


```php
if (!file_exists(\'madeline.php\')) {
    copy(\'https://phar.madelineproto.xyz/madeline.php\', \'madeline.php\');
}
include \'madeline.php\';

$MadelineProto = new \danog\MadelineProto\API(\'session.madeline\');
$MadelineProto->start();

$'.$type.' = $MadelineProto->'.$php_method.'(['.$params.']);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

'.($bot ? '### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - '.$data['method'].'
* params - `{'.$json_params.'}`

' : '').'

### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/'.$data['method'].'`

Parameters:

'.$pwr_params.'


Or, if you\'re into Lua:

```lua
'.$type.' = '.$data['method'].'({'.$lua_params.'})
```

');
                if ($hasreplymarkup) {
                    $example .= '
## Usage of reply_markup

You can provide bot API reply_markup objects here.  


';
                }
                if ($hasmessage) {
                    $example .= '
## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of ['.str_replace('_', '\\_', $type).'](../types/'.$php_type.'.md) will be returned instead.


';
                }
                if ($hasentities) {
                    $example .= '
## Usage of parse_mode:

Set parse_mode to html to enable HTML parsing of the message.  

Set parse_mode to Markdown to enable markown AND html parsing of the message.  

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
                if (isset($errors['result'][$data['method']])) {
                    $example .= '### Errors this method can return:

| Error    | Description   |
|----------|---------------|
';
                    foreach ($errors['result'][$data['method']] as $error) {
                        $example .= '|'.$error.'|'.$errors['human_result'][$error][0].'|'."\n";
                    }
                    $example .= "\n\n";
                }
            }
            file_put_contents('methods/'.$method.'.md', $header.$table.$return.$example);
        }
        $this->logger->logger('Generating methods index...', \danog\MadelineProto\Logger::NOTICE);
        ksort($this->docs_methods);
        ksort($this->human_docs_methods);
        $last_namespace = '';
        foreach ($this->docs_methods as $method => &$value) {
            $new_namespace = preg_replace('/_.*/', '', $method);
            $br = $new_namespace != $last_namespace ? '***
<br><br>
' : '';
            $value = $br.$value;
            $last_namespace = $new_namespace;
        }
        file_put_contents('methods/api_'.$this->index, '---
title: Methods
description: List of methods
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# Methods  
[Back to API documentation index](..)

[Go to the new description-version method index]('.$this->index.')

$MadelineProto->[logout](https://docs.madelineproto.xyz/logout.html)();

$MadelineProto->[phone_login](https://docs.madelineproto.xyz/phone_login.html)($number);

$MadelineProto->[complete_phone_login](https://docs.madelineproto.xyz/complete_phone_login.html)($code);

$MadelineProto->[complete_2FA_login](https://docs.madelineproto.xyz/complete_2FA_login.html)($password);

$MadelineProto->[bot_login](https://docs.madelineproto.xyz/bot_login.html)($token);


$MadelineProto->[get_dialogs](https://docs.madelineproto.xyz/get_dialogs.html)();

$MadelineProto->[get_pwr_chat](https://docs.madelineproto.xyz/get_pwr_chat.html)($id);

$MadelineProto->[get_info](https://docs.madelineproto.xyz/get_info.html)($id);

$MadelineProto->[get_full_info](https://docs.madelineproto.xyz/get_full_info.html)($id);

$MadelineProto->[get_self](https://docs.madelineproto.xyz/get_self.html)();


$MadelineProto->[request_call](https://docs.madelineproto.xyz/request_call.html)($id);

$MadelineProto->[request_secret_chat](https://docs.madelineproto.xyz/request_secret_chat.html)($id);

'.implode('', $this->docs_methods));

        file_put_contents('methods/'.$this->index, '---
title: Methods
description: What do you want to do?
image: https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png
---
# What do you want to do?  
[Go back to API documentation index](..)  

[Go to the old code-version method index](api_'.$this->index.')  

* [Logout](https://docs.madelineproto.xyz/logout.html)

* [Login](https://docs.madelineproto.xyz/docs/LOGIN.html)

* [Get all chats, broadcast a message to all chats](https://docs.madelineproto.xyz/docs/DIALOGS.html)

* [Get the full participant list of a channel/group/supergroup](https://docs.madelineproto.xyz/get_pwr_chat.html)

* [Get full info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/get_full_info.html)

* [Get info about a user/chat/supergroup/channel](https://docs.madelineproto.xyz/get_info.html)

* [Get info about the currently logged-in user](https://docs.madelineproto.xyz/get_self.html)

* [Upload or download files up to 1.5 GB](https://docs.madelineproto.xyz/docs/FILES.html)

* [Make a phone call and play a song](https://docs.madelineproto.xyz/docs/CALLS.html)

* [Create a secret chat bot](https://docs.madelineproto.xyz/docs/SECRET_CHATS.html)

'.implode('', $this->human_docs_methods));
    }
}
