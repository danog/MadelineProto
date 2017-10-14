---
title: channels.editAdmin
description: channels.editAdmin parameters, return type and example
---
## Method: channels.editAdmin  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel|[InputChannel](../types/InputChannel.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|
|admin\_rights|[ChannelAdminRights](../types/ChannelAdminRights.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNEL_INVALID|The provided channel is invalid|
|CHAT_ADMIN_INVITE_REQUIRED|You do not have the rights to do this|
|CHAT_ADMIN_REQUIRED|You must be an admin in this chat to do this|
|RIGHT_FORBIDDEN|Your admin rights do not allow you to do this|
|USER_CREATOR|You can't leave this channel, because you're its creator|
|USER_ID_INVALID|The provided user ID is invalid|
|USER_NOT_MUTUAL_CONTACT|The provided user is not a mutual contact|
|USER_PRIVACY_RESTRICTED|The user's privacy settings do not allow you to do this|


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

$Updates = $MadelineProto->channels->editAdmin(['channel' => InputChannel, 'user_id' => InputUser, 'admin_rights' => ChannelAdminRights, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - channels.editAdmin
* params - `{"channel": InputChannel, "user_id": InputUser, "admin_rights": ChannelAdminRights, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.editAdmin`

Parameters:

channel - Json encoded InputChannel

user_id - Json encoded InputUser

admin_rights - Json encoded ChannelAdminRights




Or, if you're into Lua:

```
Updates = channels.editAdmin({channel=InputChannel, user_id=InputUser, admin_rights=ChannelAdminRights, })
```

