---
title: messages.clearRecentStickers
description: Clear all recent stickers
---
## Method: messages.clearRecentStickers  
[Back to methods index](index.md)


Clear all recent stickers



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

$Bool = $MadelineProto->messages->clearRecentStickers();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.clearRecentStickers`

Parameters:




Or, if you're into Lua:

```
Bool = messages.clearRecentStickers({})
```

