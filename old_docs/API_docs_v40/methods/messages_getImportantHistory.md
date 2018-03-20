---
title: messages.getImportantHistory
description: messages.getImportantHistory parameters, return type and example
---
## Method: messages.getImportantHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|
|max\_id|[CLICK ME int](../types/int.md) | Yes|
|min\_id|[CLICK ME int](../types/int.md) | Yes|
|limit|[CLICK ME int](../types/int.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

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

$messages_Messages = $MadelineProto->messages->getImportantHistory(['peer' => InputPeer, 'max_id' => int, 'min_id' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.getImportantHistory
* params - `{"peer": InputPeer, "max_id": int, "min_id": int, "limit": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getImportantHistory`

Parameters:

peer - Json encoded InputPeer

max_id - Json encoded int

min_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.getImportantHistory({peer=InputPeer, max_id=int, min_id=int, limit=int, })
```

