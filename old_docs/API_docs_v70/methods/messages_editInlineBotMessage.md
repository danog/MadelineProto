---
title: messages.editInlineBotMessage
description: messages.editInlineBotMessage parameters, return type and example
---
## Method: messages.editInlineBotMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Yes|
|message|[string](../types/string.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|parse\_mode| [string](../types/string.md) | Optional |


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|MESSAGE_NOT_MODIFIED|The message text has not changed|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Bool = $MadelineProto->messages->editInlineBotMessage(['no_webpage' => Bool, 'id' => InputBotInlineMessageID, 'message' => 'string', 'reply_markup' => ReplyMarkup, 'entities' => [MessageEntity], 'parse_mode' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.editInlineBotMessage
* params - `{"no_webpage": Bool, "id": InputBotInlineMessageID, "message": "string", "reply_markup": ReplyMarkup, "entities": [MessageEntity], "parse_mode": "string"}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.editInlineBotMessage`

Parameters:

parse_mode - string



Or, if you're into Lua:

```
Bool = messages.editInlineBotMessage({no_webpage=Bool, id=InputBotInlineMessageID, message='string', reply_markup=ReplyMarkup, entities={MessageEntity}, parse_mode='string', })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  



## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [Bool](../types/Bool.md) will be returned instead.



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
