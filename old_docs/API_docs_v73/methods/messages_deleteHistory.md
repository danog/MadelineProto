---
title: messages.deleteHistory
description: messages.deleteHistory parameters, return type and example
---
## Method: messages.deleteHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|just\_clear|[Bool](../types/Bool.md) | Optional|
|peer|[InputPeer](../types/InputPeer.md) | Optional|
|max\_id|[int](../types/int.md) | Yes|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
$MadelineProto->session = 'mySession.madeline';
if (isset($number)) { // Login as a user
    $MadelineProto->phone_login($number);
    $code = readline('Enter the code you received: '); // Or do this in two separate steps in an HTTP API
    $MadelineProto->complete_phone_login($code);
}

$messages_AffectedHistory = $MadelineProto->messages->deleteHistory(['just_clear' => Bool, 'peer' => InputPeer, 'max_id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteHistory`

Parameters:

just_clear - Json encoded Bool

peer - Json encoded InputPeer

max_id - Json encoded int




Or, if you're into Lua:

```
messages_AffectedHistory = messages.deleteHistory({just_clear=Bool, peer=InputPeer, max_id=int, })
```

