---
title: messages.sendMessage
description: messages.sendMessage parameters, return type and example
---
## Method: messages.sendMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|broadcast|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|background|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|
|message|[string](../types/string.md) | Yes|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|parse\_mode| [string](../types/string.md) | Optional |


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BUTTON_DATA_INVALID|The provided button data is invalid|
|BUTTON_TYPE_INVALID|The type of one of the buttons you provided is invalid|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MESSAGE_EMPTY|The provided message is empty|
|MESSAGE_TOO_LONG|The provided message is too long|
|PEER_ID_INVALID|The provided peer id is invalid|
|RANDOM_ID_DUPLICATE|You provided a random ID that was already used|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|YOU_BLOCKED_USER|You blocked this user|


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

$Updates = $MadelineProto->messages->sendMessage(['no_webpage' => Bool, 'broadcast' => Bool, 'silent' => Bool, 'background' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'message' => 'string', 'reply_markup' => ReplyMarkup, 'entities' => [MessageEntity], 'parse_mode' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendMessage
* params - `{"no_webpage": Bool, "broadcast": Bool, "silent": Bool, "background": Bool, "peer": InputPeer, "reply_to_msg_id": int, "message": "string", "reply_markup": ReplyMarkup, "entities": [MessageEntity], "parse_mode": "string"}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendMessage`

Parameters:

parse_mode - string



Or, if you're into Lua:

```
Updates = messages.sendMessage({no_webpage=Bool, broadcast=Bool, silent=Bool, background=Bool, peer=InputPeer, reply_to_msg_id=int, message='string', reply_markup=ReplyMarkup, entities={MessageEntity}, parse_mode='string', })
```


## Usage of reply_markup

You can provide bot API reply_markup objects here.  



## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [Updates](../types/Updates.md) will be returned instead.



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
