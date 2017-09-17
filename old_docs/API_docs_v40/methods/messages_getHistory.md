---
title: messages.getHistory
description: messages.getHistory parameters, return type and example
---
## Method: messages.getHistory  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Yes|
|offset|[int](../types/int.md) | Yes|
|max\_id|[int](../types/int.md) | Yes|
|min\_id|[int](../types/int.md) | Yes|
|limit|[int](../types/int.md) | Yes|


### Return type: [messages\_Messages](../types/messages_Messages.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ID_INVALID|The provided chat id is invalid|
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

$messages_Messages = $MadelineProto->messages->getHistory(['peer' => InputPeer, 'offset' => int, 'max_id' => int, 'min_id' => int, 'limit' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getHistory`

Parameters:

peer - Json encoded InputPeer

offset - Json encoded int

max_id - Json encoded int

min_id - Json encoded int

limit - Json encoded int




Or, if you're into Lua:

```
messages_Messages = messages.getHistory({peer=InputPeer, offset=int, max_id=int, min_id=int, limit=int, })
```

