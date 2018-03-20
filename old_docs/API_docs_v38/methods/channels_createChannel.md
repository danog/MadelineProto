---
title: channels.createChannel
description: channels.createChannel parameters, return type and example
---
## Method: channels.createChannel  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|title|[CLICK ME string](../types/string.md) | Yes|
|about|[CLICK ME string](../types/string.md) | Yes|
|users|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_TITLE_EMPTY|No chat title provided|
|USER_RESTRICTED|You're spamreported, you can't create channels or chats.|


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

$Updates = $MadelineProto->channels->createChannel(['title' => 'string', 'about' => 'string', 'users' => [InputUser, InputUser], ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.createChannel`

Parameters:

title - Json encoded string

about - Json encoded string

users - Json encoded  array of InputUser




Or, if you're into Lua:

```
Updates = channels.createChannel({title='string', about='string', users={InputUser}, })
```

