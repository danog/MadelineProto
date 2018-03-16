---
title: messages.checkChatInvite
description: messages.checkChatInvite parameters, return type and example
---
## Method: messages.checkChatInvite  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[string](../types/string.md) | Yes|


### Return type: [ChatInvite](../types/ChatInvite.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INVITE_HASH_EMPTY|The invite hash is empty|
|INVITE_HASH_EXPIRED|The invite link has expired|
|INVITE_HASH_INVALID|The invite hash is invalid|


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

$ChatInvite = $MadelineProto->messages->checkChatInvite(['hash' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.checkChatInvite`

Parameters:

hash - Json encoded string




Or, if you're into Lua:

```
ChatInvite = messages.checkChatInvite({hash='string', })
```

