---
title: messages.sendBroadcast
description: messages.sendBroadcast parameters, return type and example
---
## Method: messages.sendBroadcast  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|contacts|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|
|message|[CLICK ME string](../types/string.md) | Yes|
|media|[MessageMedia, Update, Message or InputMedia](../types/InputMedia.md) | Optional|


### Return type: [messages\_StatedMessages](../types/messages_StatedMessages.md)

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

$messages_StatedMessages = $MadelineProto->messages->sendBroadcast(['contacts' => [InputUser, InputUser], 'message' => 'string', 'media' => InputMedia, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.sendBroadcast
* params - `{"contacts": [InputUser], "message": "string", "media": InputMedia, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.sendBroadcast`

Parameters:

contacts - Json encoded  array of InputUser

message - Json encoded string

media - Json encoded InputMedia




Or, if you're into Lua:

```
messages_StatedMessages = messages.sendBroadcast({contacts={InputUser}, message='string', media=InputMedia, })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [messages\_StatedMessages](../types/messages_StatedMessages.md) will be returned instead.


