---
title: messages.deactivateChat
description: messages.deactivateChat parameters, return type and example
---
## Method: messages.deactivateChat  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|enabled|[CLICK ME Bool](../types/Bool.md) | Yes|


### Return type: [Updates](../types/Updates.md)

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

$Updates = $MadelineProto->messages->deactivateChat(['chat_id' => InputPeer, 'enabled' => Bool, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deactivateChat
* params - `{"chat_id": InputPeer, "enabled": Bool, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deactivateChat`

Parameters:

chat_id - Json encoded InputPeer

enabled - Json encoded Bool




Or, if you're into Lua:

```
Updates = messages.deactivateChat({chat_id=InputPeer, enabled=Bool, })
```

