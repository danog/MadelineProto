---
title: messages.getWebPagePreview
description: Get webpage preview
---
## Method: messages.getWebPagePreview  
[Back to methods index](index.md)


Get webpage preview

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message|[string](../types/string.md) | Yes|Extract preview from this message|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|Entities for styled text|
|parse\_mode| [string](../types/string.md) | Optional |Whether to parse HTML or Markdown markup in the message|


### Return type: [MessageMedia](../types/MessageMedia.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$MessageMedia = $MadelineProto->messages->getWebPagePreview(['message' => 'string', 'entities' => [MessageEntity, MessageEntity], 'parse_mode' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getWebPagePreview`

Parameters:

parse_mode - string



Or, if you're into Lua:

```
MessageMedia = messages.getWebPagePreview({message='string', entities={MessageEntity}, parse_mode='string', })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [MessageMedia](../types/MessageMedia.md) will be returned instead.



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
