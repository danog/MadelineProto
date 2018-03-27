---
title: geochats.setTyping
description: Send typing notification to geochat
---
## Method: geochats.setTyping  
[Back to methods index](index.md)


Send typing notification to geochat

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[InputGeoChat](../types/InputGeoChat.md) | Yes|The geochat|
|typing|[Bool](../types/Bool.md) | Yes|Typing or not typing|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->geochats->setTyping(['peer' => InputGeoChat, 'typing' => Bool, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - geochats.setTyping
* params - `{"peer": InputGeoChat, "typing": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/geochats.setTyping`

Parameters:

peer - Json encoded InputGeoChat

typing - Json encoded Bool




Or, if you're into Lua:

```
Bool = geochats.setTyping({peer=InputGeoChat, typing=Bool, })
```

