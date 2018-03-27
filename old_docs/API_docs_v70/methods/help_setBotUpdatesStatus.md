---
title: help.setBotUpdatesStatus
description: Set the update status of webhook
---
## Method: help.setBotUpdatesStatus  
[Back to methods index](index.md)


Set the update status of webhook

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|pending\_updates\_count|[int](../types/int.md) | Yes|Pending update count|
|message|[string](../types/string.md) | Yes|Message|


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

$Bool = $MadelineProto->help->setBotUpdatesStatus(['pending_updates_count' => int, 'message' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - help.setBotUpdatesStatus
* params - `{"pending_updates_count": int, "message": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/help.setBotUpdatesStatus`

Parameters:

pending_updates_count - Json encoded int

message - Json encoded string




Or, if you're into Lua:

```
Bool = help.setBotUpdatesStatus({pending_updates_count=int, message='string', })
```


## Return value 

If the length of the provided message is bigger than 4096, the message will be split in chunks and the method will be called multiple times, with the same parameters (except for the message), and an array of [Bool](../types/Bool.md) will be returned instead.


