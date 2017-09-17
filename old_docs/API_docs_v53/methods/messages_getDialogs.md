---
title: messages.getDialogs
description: messages.getDialogs parameters, return type and example
---
## Method: messages.getDialogs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset\_date|[int](../types/int.md) | Yes|
|offset\_id|[int](../types/int.md) | Yes|
|offset\_peer|[InputPeer](../types/InputPeer.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [messages\_Dialogs](../types/messages_Dialogs.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|OFFSET_PEER_ID_INVALID|The provided offset peer is invalid|
|SESSION_PASSWORD_NEEDED|2FA is enabled, use a password to login|


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

$messages_Dialogs = $MadelineProto->messages->getDialogs(['offset_date' => int, 'offset_id' => int, 'offset_peer' => InputPeer, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDialogs`

Parameters:

offset_date - Json encoded int

offset_id - Json encoded int

offset_peer - Json encoded InputPeer

limit - Json encoded int




Or, if you're into Lua:

```
messages_Dialogs = messages.getDialogs({offset_date=int, offset_id=int, offset_peer=InputPeer, limit=int, })
```

