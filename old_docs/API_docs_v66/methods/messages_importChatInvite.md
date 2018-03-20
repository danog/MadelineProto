---
title: messages.importChatInvite
description: messages.importChatInvite parameters, return type and example
---
## Method: messages.importChatInvite  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|hash|[string](../types/string.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHANNELS_TOO_MUCH|You have joined too many channels/supergroups|
|INVITE_HASH_EMPTY|The invite hash is empty|
|INVITE_HASH_EXPIRED|The invite link has expired|
|INVITE_HASH_INVALID|The invite hash is invalid|
|USER_ALREADY_PARTICIPANT|The user is already in the group|
|USERS_TOO_MUCH|The maximum number of users has been exceeded (to create a chat, for example)|
|SESSION_PASSWORD_NEEDED|2FA is enabled, use a password to login|


### Example:


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

$Updates = $MadelineProto->messages->importChatInvite(['hash' => 'string', ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.importChatInvite`

Parameters:

hash - Json encoded string




Or, if you're into Lua:

```
Updates = messages.importChatInvite({hash='string', })
```

