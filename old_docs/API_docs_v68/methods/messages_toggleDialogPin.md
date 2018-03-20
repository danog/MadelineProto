---
title: messages.toggleDialogPin
description: messages.toggleDialogPin parameters, return type and example
---
## Method: messages.toggleDialogPin  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pinned|[CLICK ME Bool](../types/Bool.md) | Optional|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|


### Return type: [Bool](../types/Bool.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$Bool = $MadelineProto->messages->toggleDialogPin(['pinned' => Bool, 'peer' => InputPeer, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.toggleDialogPin`

Parameters:

pinned - Json encoded Bool

peer - Json encoded InputPeer




Or, if you're into Lua:

```
Bool = messages.toggleDialogPin({pinned=Bool, peer=InputPeer, })
```

