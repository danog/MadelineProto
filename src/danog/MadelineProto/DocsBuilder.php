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

namespace danog\MadelineProto;

class DocsBuilder
{
    use \danog\MadelineProto\TL\TL;
    use Tools;

    public function __construct($settings)
    {
        set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);
        $this->construct_TL($settings['tl_schema']);
        if (isset($settings['tl_schema']['td']) && !isset($settings['tl_schema']['telegram'])) {
            $this->constructors = $this->td_constructors;
        }
        if (isset($settings['tl_schema']['td']) && !isset($settings['tl_schema']['telegram'])) {
            $this->methods = $this->td_methods;
        }
        $this->settings = $settings;
        if (!file_exists($this->settings['output_dir'])) {
            mkdir($this->settings['output_dir']);
        }
        chdir($this->settings['output_dir']);
        $this->index = $settings['readme'] ? 'README.md' : 'index.md';
    }

    public function mk_docs()
    {
        $types = [];
        $any = '*';
        \danog\MadelineProto\Logger::log(['Generating documentation index...'], \danog\MadelineProto\Logger::NOTICE);

        file_put_contents($this->index, '---
title: '.$this->settings['title'].'
description: '.$this->settings['description'].'
---
# '.$this->settings['description'].'  

[Methods](methods/)

[Constructors](constructors/)

[Types](types/)


[Back to main documentation](..)
');

        foreach (glob('methods/'.$any) as $unlink) {
            unlink($unlink);
        }

        if (file_exists('methods')) {
            rmdir('methods');
        }

        mkdir('methods');

        $methods = [];

        \danog\MadelineProto\Logger::log(['Generating methods documentation...'], \danog\MadelineProto\Logger::NOTICE);

        foreach ($this->methods->method as $key => $rmethod) {
            $method = str_replace('.', '_', $rmethod);
            $real_method = str_replace('.', '->', $rmethod);
            $rtype = $this->methods->type[$key];
            $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $rtype);
            $real_type = preg_replace('/.*_of_/', '', $type);
            if (!isset($types[$real_type])) {
                $types[$real_type] = ['constructors' => [], 'methods' => []];
            }
            if (!in_array($key, $types[$real_type]['methods'])) {
                $types[$real_type]['methods'][] = $key;
            }

            $params = '';
            foreach ($this->methods->params[$key] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage') {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $rmethod !== 'messages.discardEncryption') {
                    $param['type'] = 'InputPeer';
                }
                $stype = 'type';
                $link_type = 'types';
                if (isset($param['subtype'])) {
                    $stype = 'subtype';
                    if ($param['type'] === 'vector') {
                        $link_type = 'constructors';
                    }
                }
                $ptype = str_replace('.', '_', $param[$stype]);
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                $params .= "'".$param['name']."' => ";
                $ptype =
                    '['.
                     str_replace('_', '\_', $ptype).
                    '](../'.$link_type.'/'.$ptype.'.md)';

                $params .= (isset($param['subtype']) ? '\['.$ptype.'\]' : $ptype).', ';
            }
            $md_method = '['.$real_method.']('.$method.'.md)';

            $methods[$method] = '$MadelineProto->'.$md_method.'(\['.$params.'\]) === [$'.str_replace('_', '\_', $type).'](../types/'.$real_type.'.md)<a name="'.$method.'"></a>  

';

            $params = '';
            $lua_params = '';
            $table = empty($this->methods->params[$key]) ? '' : '### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
';
            if (isset($this->td_descriptions['methods'][$rmethod])) {
                $table = '### Params:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
';
            }

            $hasentities = false;
            $hasreplymarkup = false;
            $hasmessage = false;
            foreach ($this->methods->params[$key] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($param['name'] === 'data' && $type === 'messages_SentEncryptedMessage') {
                    $param['name'] = 'message';
                    $param['type'] = 'DecryptedMessage';
                }
                if ($param['name'] === 'chat_id' && $rmethod !== 'messages.discardEncryption') {
                    $param['type'] = 'InputPeer';
                }

                $ptype = str_replace('.', '_', $param[isset($param['subtype']) ? 'subtype' : 'type']);
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                $table .= '|'.str_replace('_', '\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\_', $ptype).'](../types/'.$ptype.'.md) | '.(isset($param['pow']) ? 'Optional' : 'Yes').'|';
                if (isset($this->td_descriptions['methods'][$rmethod])) {
                    $table .= $this->td_descriptions['methods'][$rmethod]['params'][$param['name']].'|';
                }
                $table .= PHP_EOL;

                $params .= "'".$param['name']."' => ";
                $params .= (isset($param['subtype']) ? '['.$ptype.']' : $ptype).', ';
                $lua_params .= $param['name'].'=';
                $lua_params .= (isset($param['subtype']) ? '{'.$ptype.'}' : $ptype).', ';
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
                }
            }
            $description = isset($this->td_descriptions['methods'][$rmethod]) ? $this->td_descriptions['methods'][$rmethod]['description'] : ($rmethod.' parameters, return type and example');
            $header = '---
title: '.$rmethod.'
description: '.$description.'
---
## Method: '.str_replace('_', '\_', $rmethod).'  
[Back to methods index](index.md)


';
            $header .= isset($this->td_descriptions['methods'][$rmethod]) ? $this->td_descriptions['methods'][$rmethod]['description'].PHP_EOL.PHP_EOL : '';
            $table .= '

';
            $return = '### Return type: ['.str_replace('_', '\_', $type).'](../types/'.$real_type.'.md)

';
            $example = str_replace('[]', '', '### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $this->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo \'Enter the code you received: \';
    $code = \'\';
    for ($x = 0; $x < $sentCode[\'type\'][\'length\']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$'.$type.' = $MadelineProto->'.$real_method.'(['.$params.']);
```

Or, if you\'re into Lua:

```
'.$type.' = '.$rmethod.'({'.$lua_params.'})
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

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of ['.str_replace('_', '\_', $type).'](../types/'.$real_type.'.md) will be returned instead.


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
            file_put_contents('methods/'.$method.'.md', $header.$table.$return.$example);
        }

        \danog\MadelineProto\Logger::log(['Generating methods index...'], \danog\MadelineProto\Logger::NOTICE);

        ksort($methods);
        $last_namespace = '';
        foreach ($methods as $method => &$value) {
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



'.implode('', $methods));

        foreach (glob('constructors/'.$any) as $unlink) {
            unlink($unlink);
        }

        if (file_exists('constructors')) {
            rmdir('constructors');
        }

        mkdir('constructors');

        $constructors = [];
        \danog\MadelineProto\Logger::log(['Generating constructors documentation...'], \danog\MadelineProto\Logger::NOTICE);

        foreach ($this->constructors->predicate as $key => $rconstructor) {
            if (preg_match('/%/', $type)) {
                $type = $this->constructors->find_by_type(str_replace('%', '', $type))['predicate'];
            }
            $layer = isset($this->constructors->layer[$key]) ? '_'.$this->constructors->layer[$key] : '';
            $rtype = $this->constructors->type[$key];

            $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $rtype);
            $real_type = preg_replace('/.*_of_/', '', $type);
            $constructor = str_replace(['.', '<', '>'], ['_', '_of_', ''], $rconstructor);
            $real_constructor = preg_replace('/.*_of_/', '', $constructor);

            if (!isset($types[$real_type])) {
                $types[$real_type] = ['constructors' => [], 'methods' => []];
            }
            if (!in_array($key, $types[$real_type]['constructors'])) {
                $types[$real_type]['constructors'][] = $key;
            }

            $params = '';
            foreach ($this->constructors->params[$key] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($type === 'EncryptedMessage' && $param['name'] === 'bytes') {
                    $param['name'] = 'decrypted_message';
                    $param['type'] = 'DecryptedMessage';
                }
                $stype = 'type';
                $link_type = 'types';
                if (isset($param['subtype'])) {
                    $stype = 'subtype';
                    if ($param['type'] === 'vector') {
                        $link_type = 'constructors';
                    }
                }

                $ptype = str_replace('.', '_', $param[$stype]);
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                if (preg_match('/%/', $ptype)) {
                    $ptype = $this->constructors->find_by_type(str_replace('%', '', $ptype))['predicate'];
                }

                $params .= "'".$param['name']."' => ";
                $ptype =
                    '['.
                    str_replace('_', '\_', $ptype).
                    '](../'.$link_type.'/'.$ptype.'.md)';

                $params .= (isset($param['subtype']) ? '\['.$ptype.'\]' : $ptype).', ';
            }
            $md_constructor = str_replace('_', '\_', $constructor.$layer);

            $constructors[$constructor] = '[$'.$md_constructor.'](../constructors/'.$real_constructor.$layer.'.md) = \['.$params.'\];<a name="'.$constructor.$layer.'"></a>  

';

            $table = empty($this->constructors->params[$key]) ? '' : '### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
';
            if (isset($this->td_descriptions['constructors'][$rconstructor])) {
                $table = '### Attributes:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
';
            }

            $params = '';
            $lua_params = '';
            $hasreplymarkup = false;
            foreach ($this->constructors->params[$key] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($type === 'EncryptedMessage' && $param['name'] === 'bytes') {
                    $param['name'] = 'decrypted_message';
                    $param['type'] = 'DecryptedMessage';
                }
                $ptype = str_replace('.', '_', $param[isset($param['subtype']) ? 'subtype' : 'type']);

                $link_type = 'types';
                if (isset($param['subtype'])) {
                    if ($param['type'] === 'vector') {
                        $link_type = 'constructors';
                    }
                }
                if (preg_match('/%/', $ptype)) {
                    $ptype = $this->constructors->find_by_type(str_replace('%', '', $ptype))['predicate'];
                }
                switch ($ptype) {
                    case 'true':
                    case 'false':
                        $ptype = 'Bool';
                }
                $table .= '|'.str_replace('_', '\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\_', $ptype).'](../'.$link_type.'/'.$ptype.'.md) | '.(isset($param['pow']) ? 'Optional' : 'Yes').'|';
                if (isset($this->td_descriptions['constructors'][$rconstructor]['params'][$param['name']])) {
                    $table .= $this->td_descriptions['constructors'][$rconstructor]['params'][$param['name']].'|';
                }
                $table .= PHP_EOL;

                $params .= "'".$param['name']."' => ";
                $params .= (isset($param['subtype']) ? '['.$ptype.']' : $ptype).', ';
                $lua_params .= $param['name'].'=';
                $lua_params .= (isset($param['subtype']) ? '{'.$ptype.'}' : $ptype).', ';
                if ($param['name'] === 'reply_markup') {
                    $hasreplymarkup = true;
                }
            }
            $params = "['_' => '".$rconstructor."', ".$params.']';
            $lua_params = "{_='".$rconstructor."', ".$lua_params.'}';
            $description = isset($this->td_descriptions['constructors'][$rconstructor]) ? $this->td_descriptions['constructors'][$rconstructor]['description'] : ($constructor.' attributes, type and example');
            $header = '---
title: '.$rconstructor.'
description: '.$description.'
---
## Constructor: '.str_replace('_', '\_', $rconstructor.$layer).'  
[Back to constructors index](index.md)



';
            $table .= '


';
            if (isset($this->td_descriptions['constructors'][$rconstructor])) {
                $header .= $this->td_descriptions['constructors'][$rconstructor]['description'].PHP_EOL.PHP_EOL;
            }

            $type = '### Type: ['.str_replace('_', '\_', $real_type).'](../types/'.$real_type.'.md)


';
            $example = '### Example:

```
$'.$constructor.$layer.' = '.$params.';
```  

Or, if you\'re into Lua:  


```
'.$constructor.$layer.'='.$lua_params.'

```


';
            if ($hasreplymarkup) {
                $example .= '
## Usage of reply_markup

You can provide bot API reply_markup objects here.  


';
            }
            file_put_contents('constructors/'.$constructor.$layer.'.md', $header.$table.$type.$example);
        }

        \danog\MadelineProto\Logger::log(['Generating constructors index...'], \danog\MadelineProto\Logger::NOTICE);

        ksort($constructors);
        $last_namespace = '';
        foreach ($constructors as $method => &$value) {
            $new_namespace = preg_replace('/_.*/', '', $method);
            $br = $new_namespace != $last_namespace ? '***
<br><br>' : '';
            $value = $br.$value;
            $last_namespace = $new_namespace;
        }
        file_put_contents('constructors/'.$this->index, '---
title: Constructors
description: List of constructors
---
# Constructors  
[Back to API documentation index](..)



'.implode('', $constructors));

        foreach (glob('types/*') as $unlink) {
            unlink($unlink);
        }

        if (file_exists('types')) {
            rmdir('types');
        }
        mkdir('types');

        ksort($types);
        $index = '';

        \danog\MadelineProto\Logger::log(['Generating types documentation...'], \danog\MadelineProto\Logger::NOTICE);
        foreach ($types as $otype => $keys) {
            $new_namespace = preg_replace('/_.*/', '', $method);
            $br = $new_namespace != $last_namespace ? '***
<br><br>' : '';
            $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $otype);
            $type = preg_replace('/.*_of_/', '', $type);
            $index .= $br.'['.str_replace('_', '\_', $type).']('.$type.'.md)<a name="'.$type.'"></a>  

';
            $constructors = '';
            foreach ($keys['constructors'] as $key) {
                $predicate = str_replace('.', '_', $this->constructors->predicate[$key]).(isset($this->constructors->layer[$key]) ? '_'.$this->constructors->layer[$key] : '');
                $md_predicate = str_replace('_', '\_', $predicate);
                $constructors .= '['.$md_predicate.'](../constructors/'.$predicate.'.md)  

';
            }
            $methods = '';
            foreach ($keys['methods'] as $key) {
                $name = str_replace('.', '_', $this->methods->method[$key]);
                $md_name = str_replace('_', '->', $name);
                $methods .= '[$MadelineProto->'.$md_name.'](../methods/'.$name.'.md)  

';
            }
            $description = isset($this->td_descriptions['types'][$otype]) ? $this->td_descriptions['types'][$otype] : ('constructors and methods of typr '.$type);

            $header = '---
title: '.$type.'
description: constructors and methods of type '.$type.'
---
## Type: '.str_replace('_', '\_', $type).'  
[Back to types index](index.md)



';
            $header .= isset($this->td_descriptions['types'][$otype]) ? $this->td_descriptions['types'][$otype].PHP_EOL.PHP_EOL : '';
            if (in_array($type, ['User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer'])) {
                $header .= 'The following syntaxes can also be used:

```
$'.$type." = '@username'; // Username

$".$type.' = 44700; // bot API id (users)
$'.$type.' = -492772765; // bot API id (chats)
$'.$type.' = -10038575794; // bot API id (channels)

$'.$type." = 'user#44700'; // tg-cli style id (users)
$".$type." = 'chat#492772765'; // tg-cli style id (chats)
$".$type." = 'channel#38575794'; // tg-cli style id (channels)
```

A [Chat](Chat.md), a [User](User.md), an [InputPeer](InputPeer.md), an [InputUser](InputUser.md), an [InputChannel](InputChannel.md), a [Peer](Peer.md), or a [Chat](Chat.md) object can also be used.


";
            }
            if (in_array($type, ['InputEncryptedChat'])) {
                $header .= 'The following syntax can also be used:

```
$'.$type.' = -147286699; // Numeric chat id returned by request_secret_chat, can be  positive or negative
```


';
            }
            if (in_array($type, ['KeyboardButton'])) {
                $header .= 'Clicking these buttons:

To click these buttons simply run the `click` method:  

```
$result = $'.$type.'->click();
```

`$result` can be one of the following:


* A string - If the button is a keyboardButtonUrl

* [Updates](Updates.md) - If the button is a keyboardButton, the message will be sent to the chat, in reply to the message with the keyboard

* [messages_BotCallbackAnswer](messages_BotCallbackAnswer.md) - If the button is a keyboardButtonCallback or a keyboardButtonGame the button will be pressed and the result will be returned

* `false` - If the button is an unsupported button, like keyboardButtonRequestPhone, keyboardButtonRequestGeoLocation, keyboardButtonSwitchInlinekeyboardButtonBuy; you will have to parse data from these buttons manually


';
            }
            $constructors = '### Possible values (constructors):

'.$constructors.'

';
            $methods = '### Methods that return an object of this type (methods):

'.$methods.'

';
            if (file_exists('types/'.$type.'.md')) {
                \danog\MadelineProto\Logger::log([$type]);
            }
            file_put_contents('types/'.$type.'.md', $header.$constructors.$methods);
            $last_namespace = $new_namespace;
        }

        \danog\MadelineProto\Logger::log(['Generating types index...'], \danog\MadelineProto\Logger::NOTICE);

        file_put_contents('types/'.$this->index, '---
title: Types
description: List of types
---
# Types  
[Back to API documentation index](..)


'.$index);

        \danog\MadelineProto\Logger::log(['Generating additional types...'], \danog\MadelineProto\Logger::NOTICE);

        file_put_contents('types/string.md', '---
title: string
description: A UTF8 string of variable length
---
## Type: string  
[Back to constructor index](index.md)

A UTF8 string of variable length. The total length in bytes of the string must not be bigger than 16777215.
');
        file_put_contents('types/bytes.md', '---
title: bytes
description: A string of variable length
---
## Type: bytes  
[Back to constructor index](index.md)

A string of bytes of variable length, with length smaller than or equal to 16777215.
');

        file_put_contents('types/int.md', '---
title: integer
description: A 32 bit signed integer ranging from -2147483647 to 2147483647
---
## Type: int  
[Back to constructor index](index.md)

A 32 bit signed integer ranging from `-2147483647` to `2147483647`.
');

        file_put_contents('types/long.md', '---
title: long
description: A 32 bit signed integer ranging from -9223372036854775807 to 9223372036854775807
---
## Type: long  
[Back to constructor index](index.md)

A 64 bit signed integer ranging from `-9223372036854775807` to `9223372036854775807`.
');

        file_put_contents('types/int128.md', '---
title: int128
description: A 128 bit signed integer
---
## Type: int128  
[Back to constructor index](index.md)

A 128 bit signed integer represented in little-endian base256 (`string`) format.
');

        file_put_contents('types/int256.md', '---
title: int256
description: A 256 bit signed integer
---
## Type: int256
[Back to constructor index](index.md)

A 256 bit signed integer represented in little-endian base256 (`string`) format.
');

        file_put_contents('types/int512.md', '---
title: int512
description: A 512 bit signed integer
---
## Type: int512  
[Back to constructor index](index.md)

A 512 bit signed integer represented in little-endian base256 (`string`) format.
');

        file_put_contents('types/double.md', '---
title: double
description: A double precision floating point number
---
## Type: double  
[Back to constructor index](index.md)

A double precision floating point number, single precision can also be used (float).
');

        file_put_contents('types/!X.md', '---
title: !X
description: Represents a TL serialized payload
---
## Type: !X  
[Back to constructor index](index.md)

Represents a TL serialized payload.
');

        file_put_contents('types/X.md', '---
title: X
description: Represents a TL serialized payload
---
## Type: X  
[Back to constructor index](index.md)

Represents a TL serialized payload.
');

        file_put_contents('constructors/boolFalse.md', '---
title: boolFalse
description: Represents a boolean with value equal to false
---
# boolFalse  
[Back to constructor index](index.md)

        Represents a boolean with value equal to `false`.
');

        file_put_contents('constructors/boolTrue.md', '---
title: boolTrue
description: Represents a boolean with value equal to true
---
# boolTrue  
[Back to constructor index](index.md)

Represents a boolean with value equal to `true`.
');

        file_put_contents('constructors/null.md', '---
title: null
description: Represents a null value
---
# null  
[Back to constructor index](index.md)

Represents a `null` value.
');

        file_put_contents('types/Bool.md', '---
title: Bool
description: Represents a boolean.
---
# Bool  
[Back to types index](index.md)

Represents a boolean.
');

        file_put_contents('types/DataJSON.md', '---
title: DataJSON
description: Any json-encodable data
---
## Type: DataJSON
[Back to constructor index](index.md)

Any json-encodable data.
');

        \danog\MadelineProto\Logger::log(['Done!'], \danog\MadelineProto\Logger::NOTICE);
    }
}
