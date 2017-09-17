---
title: messages.readHistory
description: messages.readHistory parameters, return type and example
---
## Method: messages.readHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|offset|[int](../types/int.md) | Yes|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|PEER_ID_INVALID|The provided peer id is invalid|


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

$messages_AffectedHistory = $MadelineProto->messages->readHistory(['peer' => InputPeer, 'max_id' => int, 'offset' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readHistory`

Parameters:

peer - Json encoded InputPeer

max_id - Json encoded int

offset - Json encoded int




Or, if you're into Lua:

```
messages_AffectedHistory = messages.readHistory({peer=InputPeer, max_id=int, offset=int, })
```

