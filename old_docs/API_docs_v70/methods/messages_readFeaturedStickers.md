---
title: messages.readFeaturedStickers
description: Mark new featured stickers as read
---
## Method: messages.readFeaturedStickers  
[Back to methods index](index.md)


Mark new featured stickers as read

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|Array of [long](../types/long.md) | Yes|The stickers to mark as read|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Bool = $MadelineProto->messages->readFeaturedStickers(['id' => [long, long], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readFeaturedStickers`

Parameters:

id - Json encoded  array of long




Or, if you're into Lua:

```
Bool = messages.readFeaturedStickers({id={long}, })
```

