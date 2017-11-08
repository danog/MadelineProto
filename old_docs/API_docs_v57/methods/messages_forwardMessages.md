---
title: messages.forwardMessages
description: messages.forwardMessages parameters, return type and example
---
## Method: messages.forwardMessages  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|silent|[Bool](../types/Bool.md) | Optional|
|background|[Bool](../types/Bool.md) | Optional|
|with\_my\_score|[Bool](../types/Bool.md) | Optional|
|from\_peer|[InputPeer](../types/InputPeer.md) | Yes|
|id|Array of [int](../types/int.md) | Yes|
|to\_peer|[InputPeer](../types/InputPeer.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_ID_INVALID|The provided chat id is invalid|
|GROUPED_MEDIA_INVALID|Invalid grouped media|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|MEDIA_EMPTY|The provided media object is invalid|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|MESSAGE_IDS_EMPTY|No message ids were provided|
|PEER_ID_INVALID|The provided peer id is invalid|
|RANDOM_ID_INVALID|A provided random ID is invalid|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_IS_BLOCKED|User is blocked|
|USER_IS_BOT|Bots can't send messages to other bots|
|YOU_BLOCKED_USER|You blocked this user|
|PTS_CHANGE_EMPTY|No PTS change|
|RANDOM_ID_DUPLICATE|You provided a random ID that was already used|
|CHAT_SEND_GIFS_FORBIDDEN|You can't send gifs in this chat|
|CHAT_SEND_MEDIA_FORBIDDEN|You can't send media in this chat|
|CHAT_SEND_STICKERS_FORBIDDEN|You can't send stickers in this chat.|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|Timeout|A timeout occurred while fetching data from the bot|


### Example:


```
$MadelineProto = new \danog\MadelineProto\API();
if (isset($token)) { // Login as a bot
    $MadelineProto->bot_login($token);
}
if (isset($number)) { // Login as a user
    $sentCode = $MadelineProto->phone_login($number);
    echo 'Enter the code you received: ';
    $code = '';
    for ($x = 0; $x < $sentCode['type']['length']; $x++) {
        $code .= fgetc(STDIN);
    }
    $MadelineProto->complete_phone_login($code);
}

$Updates = $MadelineProto->messages->forwardMessages(['silent' => Bool, 'background' => Bool, 'with_my_score' => Bool, 'from_peer' => InputPeer, 'id' => [int], 'to_peer' => InputPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.forwardMessages
* params - `{"silent": Bool, "background": Bool, "with_my_score": Bool, "from_peer": InputPeer, "id": [int], "to_peer": InputPeer, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.forwardMessages`

Parameters:

silent - Json encoded Bool

background - Json encoded Bool

with_my_score - Json encoded Bool

from_peer - Json encoded InputPeer

id - Json encoded  array of int

to_peer - Json encoded InputPeer




Or, if you're into Lua:

```
Updates = messages.forwardMessages({silent=Bool, background=Bool, with_my_score=Bool, from_peer=InputPeer, id={int}, to_peer=InputPeer, })
```

