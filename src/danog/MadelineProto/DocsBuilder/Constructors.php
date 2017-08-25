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

trait Constructors
{
    public function mk_constructors()
    {
        foreach (glob('constructors/'.$this->any) as $unlink) {
            unlink($unlink);
        }
        if (file_exists('constructors')) {
            rmdir('constructors');
        }
        mkdir('constructors');
        $this->docs_constructors = [];
        \danog\MadelineProto\Logger::log(['Generating constructors documentation...'], \danog\MadelineProto\Logger::NOTICE);
        $got = [];
        foreach ($this->constructors->by_predicate_and_layer as $predicate => $id) {
            $data = $this->constructors->by_id[$id];
            if (isset($got[$id])) {
                $data['layer'] = '';
                var_dump($data);
            }
            $got[$id] = '';

            /*
            if (preg_match('/%/', $type)) {
                $type = $this->constructors->find_by_type(str_replace('%', '', $type))['predicate'];
            }*/
            $layer = isset($data['layer']) && $data['layer'] !== '' ? '_'.$data['layer'] : '';
            $type = str_replace(['.', '<', '>'], ['_', '_of_', ''], $data['type']);
            $php_type = preg_replace('/.*_of_/', '', $type);
            $constructor = str_replace(['.', '<', '>'], ['_', '_of_', ''], $data['predicate']);
            $php_constructor = preg_replace('/.*_of_/', '', $constructor);
            if (!isset($this->types[$php_type])) {
                $this->types[$php_type] = ['constructors' => [], 'methods' => []];
            }
            if (!in_array($data, $this->types[$php_type]['constructors'])) {
                $this->types[$php_type]['constructors'][] = $data;
            }
            $params = '';
            foreach ($data['params'] as $param) {
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($type === 'EncryptedMessage' && $param['name'] === 'bytes') {
                    $param['name'] = 'decrypted_message';
                    $param['type'] = 'DecryptedMessage';
                }
                $type_or_subtype = isset($param['subtype']) ? 'subtype' : 'type';
                $type_or_bare_type = (ctype_upper($this->end(explode('.', $param[$type_or_subtype]))[0]) || in_array($param[$type_or_subtype], ['!X', 'X', 'bytes', 'true', 'false', 'double', 'string', 'Bool', 'int', 'long', 'int128', 'int256', 'int512'])) ? 'types' : 'constructors';

                $param[$type_or_subtype] = str_replace(['.', 'true', 'false'], ['_', 'Bool', 'Bool'], $param[$type_or_subtype]);

                if (preg_match('/%/', $param[$type_or_subtype])) {
                    $param[$type_or_subtype] = $this->constructors->find_by_type(str_replace('%', '', $param[$type_or_subtype]))['predicate'];
                }
                $params .= "'".$param['name']."' => ";
                $param[$type_or_subtype] = '['.$this->escape($param[$type_or_subtype]).'](../'.$type_or_bare_type.'/'.$param[$type_or_subtype].'.md)';
                $params .= (isset($param['subtype']) ? '\['.$param[$type_or_subtype].'\]' : $param[$type_or_subtype]).', ';
            }
            $md_constructor = str_replace('_', '\_', $constructor.$layer);
            $this->docs_constructors[$constructor] = '[$'.$md_constructor.'](../constructors/'.$php_constructor.$layer.'.md) = \['.$params.'\];<a name="'.$constructor.$layer.'"></a>  

';
            $table = empty($data['params']) ? '' : '### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
';
            if (isset($this->td_descriptions['constructors'][$data['predicate']])) {
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
                if (in_array($param['name'], ['flags', 'random_id', 'random_bytes'])) {
                    continue;
                }
                if ($type === 'EncryptedMessage' && $param['name'] === 'bytes') {
                    $param['name'] = 'decrypted_message';
                    $param['type'] = 'DecryptedMessage';
                }
                $ptype = str_replace('.', '_', $param[isset($param['subtype']) ? 'subtype' : 'type']);
                $type_or_bare_type = 'types';
                if (isset($param['subtype'])) {
                    if ($param['type'] === 'vector') {
                        $type_or_bare_type = 'constructors';
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
                $table .= '|'.str_replace('_', '\_', $param['name']).'|'.(isset($param['subtype']) ? 'Array of ' : '').'['.str_replace('_', '\_', $ptype).'](../'.$type_or_bare_type.'/'.$ptype.'.md) | '.(isset($param['pow']) ? 'Optional' : 'Yes').'|';
                if (isset($this->td_descriptions['constructors'][$data['predicate']]['params'][$param['name']])) {
                    $table .= $this->td_descriptions['constructors'][$data['predicate']]['params'][$param['name']].'|';
                }
                $table .= PHP_EOL;
                $pptype = in_array($ptype, ['string', 'bytes']) ? "'".$ptype."'" : $ptype;
                $ppptype = in_array($ptype, ['string', 'bytes']) ? '"'.$ptype.'"' : $ptype;
                $params .= ", '".$param['name']."' => ";
                $params .= (isset($param['subtype']) ? '['.$pptype.']' : $pptype);
                $lua_params .= ', '.$param['name'].'=';
                $lua_params .= (isset($param['subtype']) ? '{'.$pptype.'}' : $pptype);
                $pwr_params .= ', "'.$param['name'].'": '.(isset($param['subtype']) ? '['.$ppptype.']' : $ppptype);
                if ($param['name'] === 'reply_markup') {
                    $hasreplymarkup = true;
                }
            }
            $params = "['_' => '".$data['predicate']."'".$params.']';
            $lua_params = "{_='".$data['predicate']."'".$lua_params.'}';
            $pwr_params = '{"_": "'.$data['predicate'].'"'.$pwr_params.'}';
            $description = isset($this->td_descriptions['constructors'][$data['predicate']]) ? $this->td_descriptions['constructors'][$data['predicate']]['description'] : ($constructor.' attributes, type and example');
            $header = '---
title: '.$data['predicate'].'
description: '.$description.'
---
## Constructor: '.str_replace('_', '\_', $data['predicate'].$layer).'  
[Back to constructors index](index.md)



';
            $table .= '


';
            if (isset($this->td_descriptions['constructors'][$data['predicate']])) {
                $header .= $this->td_descriptions['constructors'][$data['predicate']]['description'].PHP_EOL.PHP_EOL;
            }
            $type = '### Type: ['.str_replace('_', '\_', $php_type).'](../types/'.$php_type.'.md)


';
            $example = '';
            if (!isset($this->settings['td'])) {
                $example = '### Example:

```
$'.$constructor.$layer.' = '.$params.';
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
'.$pwr_params.'
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
            file_put_contents('constructors/'.$constructor.$layer.'.md', $header.$table.$type.$example);
        }
        \danog\MadelineProto\Logger::log(['Generating constructors index...'], \danog\MadelineProto\Logger::NOTICE);
        ksort($this->docs_constructors);
        $last_namespace = '';
        foreach ($this->docs_constructors as $constructor => &$value) {
            $new_namespace = preg_replace('/_.*/', '', $constructor);
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
'.implode('', $this->docs_constructors));
    }
}
