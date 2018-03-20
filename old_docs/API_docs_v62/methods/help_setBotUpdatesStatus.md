---
title: help.setBotUpdatesStatus
description: help.setBotUpdatesStatus parameters, return type and example
---
## Method: help.setBotUpdatesStatus  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pending\_updates\_count|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|


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

$Bool = $MadelineProto->help->setBotUpdatesStatus(['pending_updates_count' => int, 'message' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

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


