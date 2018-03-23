---
title: channels.inviteToChannel
description: Add users to channel/supergroup
---
## Method: channels.inviteToChannel  
[Back to methods index](index.md)


Add users to channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|channel|[Username, chat ID, Update, Message or InputChannel](../types/InputChannel.md) | Optional|The channel/supergroup|
|users|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|The users to add|


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
|INPUT_USER_DEACTIVATED|The specified user was deleted|
|USER_BANNED_IN_CHANNEL|You're banned from sending messages in supergroups/channels|
|USER_BLOCKED|User blocked|
|USER_BOT|Bots can only be admins in channels.|
|USER_ID_INVALID|The provided user ID is invalid|
|USER_KICKED|This user was kicked from this supergroup/channel|
|USER_NOT_MUTUAL_CONTACT|The provided user is not a mutual contact|
|USERS_TOO_MUCH|The maximum number of users has been exceeded (to create a chat, for example)|
|CHAT_WRITE_FORBIDDEN|You can't write in this chat|
|USER_CHANNELS_TOO_MUCH|One of the users you tried to add is already in too many channels/supergroups|
|USER_PRIVACY_RESTRICTED|The user's privacy settings do not allow you to do this|


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

// !!! This API id/API hash combination will not work !!!
// !!! You must get your own @ my.telegram.org !!!
$api_id = 0;
$api_hash = '';

$MadelineProto = new \danog\MadelineProto\API('session.madeline', ['app_info' => ['api_id' => $api_id, 'api_hash' => $api_hash]]);
$MadelineProto->start();

$Updates = $MadelineProto->channels->inviteToChannel(['channel' => InputChannel, 'users' => [InputUser, InputUser], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.inviteToChannel`

Parameters:

channel - Json encoded InputChannel

users - Json encoded  array of InputUser




Or, if you're into Lua:

```
Updates = channels.inviteToChannel({channel=InputChannel, users={InputUser}, })
```

