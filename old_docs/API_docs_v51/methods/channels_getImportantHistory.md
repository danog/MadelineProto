---
title: channels.getImportantHistory
description: channels.getImportantHistory parameters, return type and example
---
## Method: channels.getImportantHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Optional|
|offset\_id|[int](../types/int.md) | Yes|
|offset\_date|[int](../types/int.md) | Yes|
|add\_offset|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|min\_id|[int](../types/int.md) | Yes|


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

$messages_Messages = $MadelineProto->channels->getImportantHistory(['channel' => InputChannel, 'offset_id' => int, 'offset_date' => int, 'add_offset' => int, 'limit' => int, 'max_id' => int, 'min_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.getImportantHistory
* params - `{"channel": InputChannel, "offset_id": int, "offset_date": int, "add_offset": int, "limit": int, "max_id": int, "min_id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getImportantHistory`

Parameters:

channel - Json encoded InputChannel

offset_id - Json encoded int

offset_date - Json encoded int

add_offset - Json encoded int

limit - Json encoded int

max_id - Json encoded int

min_id - Json encoded int




Or, if you're into Lua:

```
messages_Messages = channels.getImportantHistory({channel=InputChannel, offset_id=int, offset_date=int, add_offset=int, limit=int, max_id=int, min_id=int, })
```

