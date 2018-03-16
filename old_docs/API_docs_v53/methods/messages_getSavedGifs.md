---
title: messages.getSavedGifs
description: messages.getSavedGifs parameters, return type and example
---
## Method: messages.getSavedGifs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[int](../types/int.md) | Yes|


### Return type: [messages\_SavedGifs](../types/messages_SavedGifs.md)

### Can bots use this method: **NO**


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

$messages_SavedGifs = $MadelineProto->messages->getSavedGifs(['hash' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getSavedGifs`

Parameters:

hash - Json encoded int




Or, if you're into Lua:

```
messages_SavedGifs = messages.getSavedGifs({hash=int, })
```

