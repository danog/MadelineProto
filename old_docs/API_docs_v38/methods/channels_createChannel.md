---
title: channels.createChannel
description: Create channel/supergroup
---
## Method: channels.createChannel  
[Back to methods index](index.md)


Create channel/supergroup

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Supergroup/channel title|
|about|[string](../types/string.md) | Yes|About text|
|users|Array of [Username, chat ID, Update, Message or InputUser](../types/InputUser.md) | Yes|Users to add to channel|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$Updates = $MadelineProto->channels->createChannel(['title' => 'string', 'about' => 'string', 'users' => [InputUser, InputUser], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



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

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_TITLE_EMPTY|No chat title provided|
|USER_RESTRICTED|You're spamreported, you can't create channels or chats.|


