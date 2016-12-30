#!/usr/bin/env php
<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'vendor/autoload.php';

$mode = 3;
\danog\MadelineProto\Logger::constructor($mode);

$TL = new \danog\MadelineProto\MTProto(['tl' => [
    //'mtproto'  => __DIR__.'/src/danog/MadelineProto/TL_mtproto_v1.json', // mtproto TL scheme
    'telegram' => __DIR__.'/src/danog/MadelineProto/TL_telegram_v57.json', // telegram TL scheme
]]);
$types = [];

\danog\MadelineProto\Logger::log('Copying readme...');

file_put_contents('docs/index.md', '---
title: MadelineProto documentation
description: PHP implementation of telegram\'s MTProto protocol
---
'.file_get_contents('README.md'));

chdir(__DIR__.'/docs/API_docs');

\danog\MadelineProto\Logger::log('Generating documentation index...');

file_put_contents('index.md', '---
title: MadelineProto API documentation (layer 57)
description: MadelineProto API documentation (layer 57)
---
# MadelineProto API documentation (layer 57) 

[Methods](methods/)

[Constructors](constructors/)

[Types](types/)


[Back to main documentation](..)
');

foreach (glob('methods/*') as $unlink) {
    unlink($unlink);
}

if (file_exists('methods')) {
    rmdir('methods');
}

mkdir('methods');

$methods = [];

\danog\MadelineProto\Logger::log('Generating methods documentation...');

foreach ($TL->methods->method as $key => $method) {
    $method = str_replace('.', '_', $method);

    $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $TL->methods->type[$key]);
    $real_type = preg_replace('/.*_of_/', '', $type);

    if (!isset($types[$real_type])) {
        $types[$real_type] = ['constructors' => [], 'methods' => []];
    }
    if (!in_array($key, $types[$real_type]['methods'])) {
        $types[$real_type]['methods'][] = $key;
    }

    $params = '';
    foreach ($TL->methods->params[$key] as $param) {
        if (in_array($param['name'], ['flags', 'random_id'])) {
            continue;
        }
        $stype = 'type';
        $link_type = 'types';
        if (isset($param['subtype'])) {
            $stype = 'subtype';
            if ($param['type'] == 'vector') {
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
    $md_method = '['.str_replace('_', '->', $method).']('.$method.'.md)';

    $methods[$method] = '$MadelineProto->'.$md_method.'(\['.$params.'\]) == [$'.str_replace('_', '\_', $type).'](../types/'.$real_type.'.md)<a name="'.$method.'"></a>  

';

    $params = '';
    $table = empty($TL->methods->params[$key]) ? '' : '### Parameters:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
';
    foreach ($TL->methods->params[$key] as $param) {
        if (in_array($param['name'], ['flags', 'random_id'])) {
            continue;
        }
        $ptype = str_replace('.', '_', $param[isset($param['subtype']) ? 'subtype' : 'type']);
        switch ($ptype) {
            case 'true':
            case 'false':
                $ptype = 'Bool';
        }
        $table .= '|'.str_replace('_', '\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\_', $ptype).'](../types/'.$ptype.'.md) | '.($param['flag'] ? 'Optional' : 'Required').'|
';

        $params .= "'".$param['name']."' => ";
        $params .= (isset($param['subtype']) ? '['.$ptype.']' : $ptype).', ';
    }
    $header = '---
title: '.$method.'
description: '.$method.' parameters, return type and example
---
## Method: '.str_replace('_', '\_', $method).'  
[Back to methods index](index.md)


';
    $table .= '

';
    $return = '### Return type: ['.str_replace('_', '\_', $type).'](../types/'.$real_type.'.md)

';
    $example = str_replace('[]', '', '### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) {
    $this->bot_login($token);
}
if (isset($number)) {
    $sentCode = $MadelineProto->phone_login($number);
    echo \'Enter the code you received: \';
    $code = \'\';
    for ($x = 0; $x < $sentCode[\'type\'][\'length\']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$'.$type.' = $MadelineProto->'.str_replace('_', '->', $method).'(['.$params.']);
```');
    file_put_contents('methods/'.$method.'.md', $header.$table.$return.$example);
}

\danog\MadelineProto\Logger::log('Generating methods index...');

ksort($methods);
$last_namespace = '';
foreach ($methods as $method => &$value) {
    $new_namespace = preg_replace('/_.*/', '', $method);
    $br = $new_namespace != $last_namespace ? '***
<br><br>' : '';
    $value = $br.$value;
    $last_namespace = $new_namespace;
}

file_put_contents('methods/index.md', '---
title: Methods
description: List of methods
---
# Methods  
[Back to API documentation index](..)



'.implode('', $methods));

foreach (glob('constructors/*') as $unlink) {
    unlink($unlink);
}

if (file_exists('constructors')) {
    rmdir('constructors');
}

mkdir('constructors');

$constructors = [];

\danog\MadelineProto\Logger::log('Generating constructors documentation...');

foreach ($TL->constructors->predicate as $key => $constructor) {
    $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $TL->constructors->type[$key]);
    $real_type = preg_replace('/.*_of_/', '', $type);

    $constructor = str_replace(['.', '<', '>'], ['_', '_of_', ''], $constructor);
    $real_constructor = preg_replace('/.*_of_/', '', $constructor);

    $params = '';
    foreach ($TL->constructors->params[$key] as $param) {
        if (in_array($param['name'], ['flags', 'random_id'])) {
            continue;
        }
        $stype = 'type';
        $link_type = 'types';
        if (isset($param['subtype'])) {
            $stype = 'subtype';
            if ($param['type'] == 'vector') {
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
    $md_constructor = str_replace('_', '\_', $constructor);

    $constructors[$constructor] = '[$'.$md_constructor.'](../constructors/'.$real_constructor.'.md) = \['.$params.'\];<a name="'.$constructor.'"></a>  

';

    if (!isset($types[$real_type])) {
        $types[$real_type] = ['constructors' => [], 'methods' => []];
    }
    if (!in_array($key, $types[$real_type]['constructors'])) {
        $types[$real_type]['constructors'][] = $key;
    }
    $table = empty($TL->constructors->params[$key]) ? '' : '### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
';
    $params = '';
    foreach ($TL->constructors->params[$key] as $param) {
        if (in_array($param['name'], ['flags', 'random_id'])) {
            continue;
        }
        $ptype = str_replace('.', '_', $param[isset($param['subtype']) ? 'subtype' : 'type']);

        $link_type = 'types';
        if (isset($param['subtype'])) {
            if ($param['type'] == 'vector') {
                $link_type = 'constructors';
            }
        }
        switch ($ptype) {
            case 'true':
            case 'false':
                $ptype = 'Bool';
        }
        $table .= '|'.str_replace('_', '\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\_', $ptype).'](../'.$link_type.'/'.$ptype.'.md) | '.($param['flag'] ? 'Optional' : 'Required').'|
';

        $params .= "'".$param['name']."' => ";
        $params .= (isset($param['subtype']) ? '['.$param['type'].']' : $param['type']).', ';
    }
    $params = "['_' => ".$constructor.', '.$params.']';

    $header = '---
title: '.$constructor.'
description: '.$constructor.' attributes, type and example
---
## Constructor: '.str_replace('_', '\_', $constructor).'  
[Back to constructors index](index.md)



';
    $table .= '


';
    $type = '### Type: ['.str_replace('_', '\_', $real_type).'](../types/'.$real_type.'.md)


';
    $example = '### Example:

```
$'.$constructor.' = '.$params.';
```';
    file_put_contents('constructors/'.$constructor.'.md', $header.$table.$type.$example);
}

\danog\MadelineProto\Logger::log('Generating constructors index...');

ksort($constructors);
$last_namespace = '';
foreach ($constructors as $method => &$value) {
    $new_namespace = preg_replace('/_.*/', '', $method);
    $br = $new_namespace != $last_namespace ? '***
<br><br>' : '';
    $value = $br.$value;
    $last_namespace = $new_namespace;
}
file_put_contents('constructors/index.md', '---
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

\danog\MadelineProto\Logger::log('Generating types documentation...');

$old_namespace = '';
foreach ($types as $type => $keys) {
    $new_namespace = preg_replace('/_.*/', '', $method);
    $br = $new_namespace != $last_namespace ? '***
<br><br>' : '';
    $type = str_replace('.', '_', $type);

    $index .= $br.'['.str_replace('_', '\_', $type).']('.$type.'.md)<a name="'.$type.'"></a>  

';
    $constructors = '';
    foreach ($keys['constructors'] as $key) {
        $predicate = str_replace('.', '_', $TL->constructors->predicate[$key]);
        $md_predicate = str_replace('_', '\_', $predicate);
        $constructors .= '['.$md_predicate.'](../constructors/'.$predicate.'.md)  

';
    }

    $methods = '';
    foreach ($keys['methods'] as $key) {
        $name = str_replace('.', '_', $TL->methods->method[$key]);
        $md_name = str_replace('_', '->', $name);
        $methods .= '[$MadelineProto->'.$md_name.'](../methods/'.$name.'.md)  

';
    }

    $header = '---
title: '.$type.'
description: constructors and methods of type '.$type.'
---
## Type: '.str_replace('_', '\_', $type).'  
[Back to types index](index.md)



';
    $constructors = '### Possible values (constructors):

'.$constructors.'

';
    $methods = '### Methods that return an object of this type (methods):

'.$methods.'

';
    file_put_contents('types/'.$type.'.md', $header.$constructors.$methods);
    $last_namespace = $new_namespace;
}

\danog\MadelineProto\Logger::log('Generating types index...');

file_put_contents('types/index.md', '---
title: Types
description: List of types
---
# Types  
[Back to API documentation index](..)


'.$index);

\danog\MadelineProto\Logger::log('Generating additional types...');

file_put_contents('types/string.md', '---
title: string
description: A string of variable length
---
## Type: string  
[Back to constructor index](index.md)

A string of variable length.');
file_put_contents('types/bytes.md', '---
title: bytes
description: A string of variable length
---
## Type: bytes  
[Back to constructor index](index.md)

A string of variable length.');

file_put_contents('types/int.md', '---
title: integer
description: A 32 bit signed integer ranging from -2147483647 to 2147483647
---
## Type: int  
[Back to constructor index](index.md)

A 32 bit signed integer ranging from `-2147483647` to `2147483647`.');

file_put_contents('types/long.md', '---
title: long
description: A 32 bit signed integer ranging from -9223372036854775807 to 9223372036854775807
---
## Type: long  
[Back to constructor index](index.md)

A 64 bit signed integer ranging from `-9223372036854775807` to `9223372036854775807`.');

file_put_contents('types/double.md', '---
title: double
description: A double precision floating point number
---
## Type: double  
[Back to constructor index](index.md)

A double precision floating point number, single precision can also be used (float).');

file_put_contents('types/!X.md', '---
title: !X
description: Represents a TL serialized payload
---
## Type: !X  
[Back to constructor index](index.md)

Represents a TL serialized payload.');

file_put_contents('types/X.md', '---
title: X
description: Represents a TL serialized payload
---
## Type: X  
[Back to constructor index](index.md)

Represents a TL serialized payload.');

file_put_contents('constructors/boolFalse.md', '---
title: boolFalse
description: Represents a boolean with value equal to false
---
# boolFalse  
[Back to constructor index](index.md)

Represents a boolean with value equal to `false`.');

file_put_contents('constructors/boolTrue.md', '---
title: boolTrue
description: Represents a boolean with value equal to true
---
# boolTrue  
[Back to constructor index](index.md)

Represents a boolean with value equal to `true`.');

file_put_contents('constructors/null.md', '---
title: null
description: Represents a null value
---
# null  
[Back to constructor index](index.md)

Represents a `null` value.');

file_put_contents('types/Bool.md', '---
title: Bool
description: Represents a boolean.
---
# Bool  
[Back to types index](index.md)

Represents a boolean.');

\danog\MadelineProto\Logger::log('Done!');
