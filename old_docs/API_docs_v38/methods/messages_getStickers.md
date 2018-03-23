---
title: messages.getStickers
description: Get stickers
---
## Method: messages.getStickers  
[Back to methods index](index.md)


Get stickers

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|emoticon|[CLICK ME string](../types/string.md) | Yes|Search by emoji|
|hash|[CLICK ME string](../types/string.md) | Yes|0 or $MadelineProto->gen_vector_hash(previously fetched sticker IDs)|


### Return type: [messages\_Stickers](../types/messages_Stickers.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$messages_Stickers = $MadelineProto->messages->getStickers(['emoticon' => 'string', 'hash' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getStickers
* params - `{"emoticon": "string", "hash": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getStickers`

Parameters:

emoticon - Json encoded string

hash - Json encoded string




Or, if you're into Lua:

```
messages_Stickers = messages.getStickers({emoticon='string', hash='string', })
```

