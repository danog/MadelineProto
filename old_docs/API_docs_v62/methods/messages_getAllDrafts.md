---
title: messages.getAllDrafts
description: Get all message drafts
---
## Method: messages.getAllDrafts  
[Back to methods index](index.md)


Get all message drafts



### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->messages->getAllDrafts();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getAllDrafts`

Parameters:




Or, if you're into Lua:

```
Updates = messages.getAllDrafts({})
```

