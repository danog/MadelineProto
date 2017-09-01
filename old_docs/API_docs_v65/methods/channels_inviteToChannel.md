---
title: channels.inviteToChannel
description: channels.inviteToChannel parameters, return type and example
---
## Method: channels.inviteToChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|users|Array of [InputUser](../types/InputUser.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|BOT_GROUPS_BLOCKED|This bot can't be added to groups|
|BOTS_TOO_MUCH|There are too many bots in this chat/channel|
|CHANNEL_INVALID|The provided channel is invalid|
|CHANNEL_PRIVATE|You haven't joined this channel/supergroup|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_CHANNELS_TOO_MUCH|One of the users you tried to add is already in too many channels/supergroups|
|USER_ID_INVALID|The provided user ID is invalid|
|USER_KICKED|This user was kicked from this supergroup/channel|
|USER_NOT_MUTUAL_CONTACT|The provided user is not a mutual contact|
|USER_PRIVACY_RESTRICTED|The user's privacy settings do not allow you to do this|


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

$Updates = $MadelineProto->channels->inviteToChannel(['channel' => InputChannel, 'users' => [InputUser], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.inviteToChannel`

Parameters:

channel - Json encoded InputChannel

users - Json encoded  array of InputUser




Or, if you're into Lua:

```
Updates = channels.inviteToChannel({channel=InputChannel, users={InputUser}, })
```

