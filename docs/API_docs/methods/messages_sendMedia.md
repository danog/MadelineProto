---
title: messages.sendMedia
description: Send a media
---
## Method: messages.sendMedia  
[Back to methods index](index.md)


Send a media

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|silent|[Bool](../types/Bool.md) | Optional|Disable notifications?|
|background|[Bool](../types/Bool.md) | Optional|Disable background notifications?|
|clear\_draft|[Bool](../types/Bool.md) | Optional|Clear the message draft of this chat?|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|Where to send the media|
|reply\_to\_msg\_id|[int](../types/int.md) | Optional|Reply to message by ID|
|media|[MessageMedia, Update, Message or InputMedia](../types/InputMedia.md) | Optional|The media to send|
|message|[string](../types/string.md) | Yes|The caption|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|Keyboards to send|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|Entities for styled text|
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

$Updates = $MadelineProto->messages->sendMedia(['silent' => Bool, 'background' => Bool, 'clear_draft' => Bool, 'peer' => InputPeer, 'reply_to_msg_id' => int, 'media' => InputMedia, 'message' => 'string', 'reply_markup' => ReplyMarkup, 'entities' => [MessageEntity, MessageEntity], 'parse_mode' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendMedia
* params - `{"silent": Bool, "background": Bool, "clear_draft": Bool, "peer": InputPeer, "reply_to_msg_id": int, "media": InputMedia, "message": "string", "reply_markup": ReplyMarkup, "entities": [MessageEntity], "parse_mode": "string"}`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendMedia`

Parameters:

parse_mode - string



Or, if you're into Lua:

```
Updates = messages.sendMedia({silent=Bool, background=Bool, clear_draft=Bool, peer=InputPeer, reply_to_msg_id=int, media=InputMedia, message='string', reply_markup=ReplyMarkup, entities={MessageEntity}, parse_mode='string', })
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
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|EXTERNAL_URL_INVALID|External URL invalid|
|FILE_PART_LENGTH_INVALID|The length of a file part is invalid|
|FILE_PARTS_INVALID|The number of file parts is invalid|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MEDIA_CAPTION_TOO_LONG|The caption is too long|
|MEDIA_EMPTY|The provided media object is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|PHOTO_EXT_INVALID|The extension of the photo is invalid|
|PHOTO_INVALID_DIMENSIONS|The photo dimensions are invalid|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|WEBPAGE_CURL_FAILED|Failure while fetching the webpage with cURL|
|WEBPAGE_MEDIA_EMPTY|Webpage media empty|
|RANDOM_ID_DUPLICATE|You provided a random ID that was already used|
|STORAGE_CHECK_FAILED|Server storage check failed|
|CHAT_SEND_MEDIA_FORBIDDEN|You can't send media in this chat|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|Timeout|A timeout occurred while fetching data from the bot|


