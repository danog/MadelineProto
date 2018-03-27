---
title: messages.checkChatInvite
description: Check if an invitation link is valid
---
## Method: messages.checkChatInvite  
[Back to methods index](index.md)


Check if an invitation link is valid

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|hash|[string](../types/string.md) | Yes|The invite link in t.me/joinchat/aflakf format|


### Return type: [ChatInvite](../types/ChatInvite.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$ChatInvite = $MadelineProto->messages->checkChatInvite(['hash' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.checkChatInvite`

Parameters:

hash - Json encoded string




Or, if you're into Lua:

```
ChatInvite = messages.checkChatInvite({hash='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INVITE_HASH_EMPTY|The invite hash is empty|
|INVITE_HASH_EXPIRED|The invite link has expired|
|INVITE_HASH_INVALID|The invite hash is invalid|


