---
title: messages.reorderStickerSets
description: messages.reorderStickerSets parameters, return type and example
---
## Method: messages.reorderStickerSets  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|masks|[CLICK ME Bool](../types/Bool.md) | Optional|
|order|Array of [CLICK ME long](../types/long.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### MadelineProto Example:


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

$Bool = $MadelineProto->messages->reorderStickerSets(['masks' => Bool, 'order' => [long, long], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.reorderStickerSets`

Parameters:

masks - Json encoded Bool

order - Json encoded  array of long




Or, if you're into Lua:

```
Bool = messages.reorderStickerSets({masks=Bool, order={long}, })
```

