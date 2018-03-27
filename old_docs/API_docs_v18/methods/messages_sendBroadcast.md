---
title: messages.sendBroadcast
description: Send a message to all users in the chat list
---
## Method: messages.sendBroadcast  
[Back to methods index](index.md)


Send a message to all users in the chat list

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|contacts|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|The users to which send the message|
|message|[string](../types/string.md) | Yes|The message|
|media|[MessageMedia, Update, Message or InputMedia](../types/InputMedia.md) | Optional|The media|


### Return type: [messages\_StatedMessages](../types/messages_StatedMessages.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
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


