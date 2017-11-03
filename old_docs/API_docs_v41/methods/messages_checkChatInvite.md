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
$MadelineProto = new \danog\MadelineProto\API();
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

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

