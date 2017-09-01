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

namespace danog\MadelineProto\DocsBuilder;

trait Methods
{
    public function mk_methods()
    {
        $bots = json_decode(file_get_contents('https://rpc.pwrtelegram.xyz/?bot'), true)['result'];
        $errors = json_decode(file_get_contents('https://rpc.pwrtelegram.xyz/?all'), true);
        foreach (glob('methods/'.$this->any) as $unlink) {
            unlink($unlink);
        }

        if (file_exists('methods')) {
            rmdir('methods');
        }

        mkdir('methods');

        $this->docs_methods = [];

        \danog\MadelineProto\Logger::log(['Generating methods documentation...'], \danog\MadelineProto\Logger::NOTICE);
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
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage') {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $data['method'] !== 'messages.discardEncryption') {
                    $param['type'] = 'InputPeer';
                }
                $type_or_subtype = isset($param['subtype']) ? 'subtype' : 'type';
                $type_or_bare_type = (ctype_upper($this->end(explode('.', $param[$type_or_subtype]))[0]) || in_array($param[$type_or_subtype], ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int', 'long', 'int128', 'int256', 'int512'])) ? 'types' : 'constructors';
                $param[$type_or_subtype] = str_replace(['.', 'true', 'false'], ['_', 'Bool', 'Bool'], $param[$type_or_subtype]);

                $param[$type_or_subtype] = '['.$this->escape($param[$type_or_subtype]).'](../'.$type_or_bare_type.'/'.$param[$type_or_subtype].'.md)';

                $params .= "'".$param['name']."' => ".(isset($param['subtype']) ? '\['.$param[$type_or_subtype].'\]' : $param[$type_or_subtype]).', ';
            }
            $md_method = '['.$php_method.']('.$method.'.md)';

            $this->docs_methods[$method] = '$MadelineProto->'.$md_method.'(\['.$params.'\]) === [$'.str_replace('_', '\_', $type).'](../types/'.$php_type.'.md)<a name="'.$method.'"></a>  

';

            $params = '';
            $lua_params = '';
            $pwr_params = '';
            $json_params = '';
            $table = empty($data['params']) ? '' : '### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
';
            if (isset($this->td_descriptions['methods'][$data['method']])) {
                $table = '### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
';
            }

            $hasentities = false;
            $hasreplymarkup = false;
            $hasmessage = false;
            foreach ($data['params'] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage') {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $data['method'] !== 'messages.discardEncryption') {
                    $param['type'] = 'InputPeer';
                }

                $ptype = str_replace('.', '_', $param[isset($param['subtype']) ? 'subtype' : 'type']);
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                $table .= '|'.str_replace('_', '\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\_', $ptype).'](../types/'.$ptype.'.md) | '.(isset($param['pow']) ? 'Optional' : 'Yes').'|';
                if (isset($this->td_descriptions['methods'][$data['method']])) {
                    $table .= $this->td_descriptions['methods'][$data['method']]['params'][$param['name']].'|';
                }
                $table .= PHP_EOL;

                $pptype = in_array($ptype, ['string', 'bytes']) ? "'".$ptype."'" : $ptype;
                $ppptype = in_array($ptype, ['string', 'bytes']) ? '"'.$ptype.'"' : $ptype;

                $params .= "'".$param['name']."' => ";
                $params .= (isset($param['subtype']) ? '['.$pptype.']' : $pptype).', ';
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
                    $table .= '|parse\_mode| [string](../types/string.md) | Optional |
';
                    $params .= "'parse_mode' => 'string', ";
                    $lua_params .= "parse_mode='string', ";
                    $json_params .= '"parse_mode": "string"';
                    $pwr_params = "parse_mode - string\n";
                }
            }
            $description = isset($this->td_descriptions['methods'][$data['method']]) ? $this->td_descriptions['methods'][$data['method']]['description'] : ($data['method'].' parameters, return type and example');
            $header = '---
title: '.$data['method'].'
description: '.$description.'
---
## Method: '.str_replace('_', '\_', $data['method']).'  
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
            $return = '### Return type: ['.str_replace('_', '\_', $type).'](../types/'.$php_type.'.md)

';
            $bot = !in_array($data['method'], $bots);
            $example = '';
            if (!isset($this->settings['td'])) {
                $example .= '### Can bots use this method: **'.($bot ? 'YES' : 'NO')."**\n\n\n";
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
                $example .= str_replace('[]', '', '### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
'.($bot ? 'if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
' : '').'if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo \'Enter the code you received: \';
    $code = \'\';
    for ($x = 0; $x < $sentCode[\'type\'][\'length\']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$'.$type.' = $MadelineProto->'.$php_method.'(['.$params.']);
```

Or, if you\'re using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

```
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

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of ['.str_replace('_', '\_', $type).'](../types/'.$php_type.'.md) will be returned instead.


';
                }
                if ($hasentities) {
                    $example .= '
## Usage of parse_mode:

Set parse_mode to html to enable HTML parsing of the message.  

Set parse_mode to Markdown to enable markown AND html parsing of the message.  

The following tags are currently supported:

```
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

```
[Mention by username](mention:@danogentili)
[Mention by user id](mention:186785362)
```

MadelineProto supports all html entities supported by [html_entity_decode](http://php.net/manual/en/function.html-entity-decode.php).
';
                }
            }
            file_put_contents('methods/'.$method.'.md', $header.$table.$return.$example);
        }

        \danog\MadelineProto\Logger::log(['Generating methods index...'], \danog\MadelineProto\Logger::NOTICE);

        ksort($this->docs_methods);
        $last_namespace = '';
        foreach ($this->docs_methods as $method => &$value) {
            $new_namespace = preg_replace('/_.*/', '', $method);
            $br = $new_namespace != $last_namespace ? '***
<br><br>' : '';
            $value = $br.$value;
            $last_namespace = $new_namespace;
        }

        file_put_contents('methods/'.$this->index, '---
title: Methods
description: List of methods
---
# Methods  
[Back to API documentation index](..)


$MadelineProto->[logout](https://docs.madelineproto.xyz/logout.html)();

$MadelineProto->[phone_login](https://docs.madelineproto.xyz/phone_login.html)($number);

$MadelineProto->[complete_phone_login](https://docs.madelineproto.xyz/complete_phone_login.html)($code);

$MadelineProto->[complete_2FA_login](https://docs.madelineproto.xyz/complete_2FA_login.html)($password);

$MadelineProto->[bot_login](https://docs.madelineproto.xyz/complete_phone_login.html)($token);


$MadelineProto->[get_dialogs](https://docs.madelineproto.xyz/get_dialogs.html)();

$MadelineProto->[get_pwr_chat](https://docs.madelineproto.xyz/get_pwr_chat.html)($id);

$MadelineProto->[get_info](https://docs.madelineproto.xyz/get_info.html)($id);

$MadelineProto->[get_full_info](https://docs.madelineproto.xyz/get_full_info.html)($id);

$MadelineProto->[get_self](https://docs.madelineproto.xyz/get_self.html)();


'.implode('', $this->docs_methods));
    }
}
