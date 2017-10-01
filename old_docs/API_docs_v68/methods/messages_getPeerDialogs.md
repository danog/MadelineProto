---
title: messages.getPeerDialogs
description: messages.getPeerDialogs parameters, return type and example
---
## Method: messages.getPeerDialogs  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peers|Array of [InputPeer](../types/InputPeer.md) | Yes|


### Return type: [messages\_PeerDialogs](../types/messages_PeerDialogs.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
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

$messages_PeerDialogs = $MadelineProto->messages->getPeerDialogs(['peers' => [InputPeer], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.getPeerDialogs`

Parameters:

peers - Json encoded  array of InputPeer




Or, if you're into Lua:

```
messages_PeerDialogs = messages.getPeerDialogs({peers={InputPeer}, })
```

