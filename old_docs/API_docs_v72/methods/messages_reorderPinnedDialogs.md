---
title: messages.reorderPinnedDialogs
description: messages.reorderPinnedDialogs parameters, return type and example
---
## Method: messages.reorderPinnedDialogs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|force|[Bool](../types/Bool.md) | Optional|
|order|Array of [InputPeer](../types/InputPeer.md) | Yes|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$Bool = $MadelineProto->messages->reorderPinnedDialogs(['force' => Bool, 'order' => [InputPeer], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.reorderPinnedDialogs`

Parameters:

force - Json encoded Bool

order - Json encoded  array of InputPeer




Or, if you're into Lua:

```
Bool = messages.reorderPinnedDialogs({force=Bool, order={InputPeer}, })
```

