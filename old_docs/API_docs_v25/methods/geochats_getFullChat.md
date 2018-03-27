---
title: geochats.getFullChat
description: Get full info about a geochat
---
## Method: geochats.getFullChat  
[Back to methods index](index.md)


Get full info about a geochat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|The geochat|


### Return type: [messages\_ChatFull](../types/messages_ChatFull.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_ChatFull = $MadelineProto->geochats->getFullChat(['peer' => InputGeoChat, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.getFullChat
* params - `{"peer": InputGeoChat, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.getFullChat`

Parameters:

peer - Json encoded InputGeoChat




Or, if you're into Lua:

```
messages_ChatFull = geochats.getFullChat({peer=InputGeoChat, })
```

