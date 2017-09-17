---
title: channels.editBanned
description: channels.editBanned parameters, return type and example
---
## Method: channels.editBanned  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|banned\_rights|[ChannelBannedRights](../types/ChannelBannedRights.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|USER_ADMIN_INVALID|You're not an admin|
|USER_ID_INVALID|The provided user ID is invalid|


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

$Updates = $MadelineProto->channels->editBanned(['channel' => InputChannel, 'user_id' => InputUser, 'banned_rights' => ChannelBannedRights, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.editBanned
* params - `{"channel": InputChannel, "user_id": InputUser, "banned_rights": ChannelBannedRights, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.editBanned`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

banned_rights - Json encoded ChannelBannedRights




Or, if you're into Lua:

```
Updates = channels.editBanned({channel=InputChannel, user_id=InputUser, banned_rights=ChannelBannedRights, })
```

