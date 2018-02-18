---
title: messages.getDialogs
description: messages.getDialogs parameters, return type and example
---
## Method: messages.getDialogs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|exclude\_pinned|[Bool](../types/Bool.md) | Optional|
|offset\_date|[int](../types/int.md) | Yes|
|offset\_id|[int](../types/int.md) | Yes|
|offset\_peer|[InputPeer](../types/InputPeer.md) | Optional|
|limit|[int](../types/int.md) | Yes|


### Return type: [messages\_Dialogs](../types/messages_Dialogs.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|INPUT_CONSTRUCTOR_INVALID|The provided constructor is invalid|
|OFFSET_PEER_ID_INVALID|The provided offset peer is invalid|
|SESSION_PASSWORD_NEEDED|2FA is enabled, use a password to login|
|Timeout|A timeout occurred while fetching data from the bot|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$messages_Dialogs = $MadelineProto->messages->getDialogs(['exclude_pinned' => Bool, 'offset_date' => int, 'offset_id' => int, 'offset_peer' => InputPeer, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getDialogs`

Parameters:

exclude_pinned - Json encoded Bool

offset_date - Json encoded int

offset_id - Json encoded int

offset_peer - Json encoded InputPeer

limit - Json encoded int




Or, if you're into Lua:

```
messages_Dialogs = messages.getDialogs({exclude_pinned=Bool, offset_date=int, offset_id=int, offset_peer=InputPeer, limit=int, })
```

