---
title: messages.editMessage
description: Edit a message
---
## Method: messages.editMessage  
[Back to methods index](index.md)


Edit a message

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|no\_webpage|[Bool](../types/Bool.md) | Optional|Disable webpage preview|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat|
|id|[int](../types/int.md) | Yes|The message ID|
|message|[string](../types/string.md) | Optional|The new message|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|The new keyboard|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|The new entities (for styled text)|
|parse\_mode| [string](../types/string.md) | Optional |Whether to parse HTML or Markdown markup in the message|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->messages->editMessage(['no_webpage' => Bool, 'peer' => InputPeer, 'id' => int, 'message' => 'string', 'reply_markup' => ReplyMarkup, 'entities' => [MessageEntity, MessageEntity], 'parse_mode' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.editMessage
* params - `{"no_webpage": Bool, "peer": InputPeer, "id": int, "message": "string", "reply_markup": ReplyMarkup, "entities": [MessageEntity], "parse_mode": "string"}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.editMessage`

Parameters:

parse_mode - string



Or, if you're into Lua:

```
Updates = messages.editMessage({no_webpage=Bool, peer=InputPeer, id=int, message='string', reply_markup=ReplyMarkup, entities={MessageEntity}, parse_mode='string', })
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
### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MESSAGE_EDIT_TIME_EXPIRED|You can't edit this message anymore, too much time has passed since its creation.|
|MESSAGE_EMPTY|The provided message is empty|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|MESSAGE_NOT_MODIFIED|The message text has not changed|
|PEER_ID_INVALID|The provided peer id is invalid|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|MESSAGE_AUTHOR_REQUIRED|Message author required|


