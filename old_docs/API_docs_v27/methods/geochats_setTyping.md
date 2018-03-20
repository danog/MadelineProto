---
title: geochats.setTyping
description: geochats.setTyping parameters, return type and example
---
## Method: geochats.setTyping  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[CLICK ME InputGeoChat](../types/InputGeoChat.md) | Yes|
|typing|[CLICK ME Bool](../types/Bool.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **YES**


### Example:


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

$Bool = $MadelineProto->geochats->setTyping(['peer' => InputGeoChat, 'typing' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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

