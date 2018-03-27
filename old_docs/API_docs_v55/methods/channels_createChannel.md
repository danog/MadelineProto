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
|broadcast|[Bool](../types/Bool.md) | Optional|Set this to true to create a channel|
|megagroup|[Bool](../types/Bool.md) | Optional|Set this to true to create a supergroup|
|title|[string](../types/string.md) | Yes|Supergroup/channel title|
|about|[string](../types/string.md) | Yes|About text|


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

$Updates = $MadelineProto->channels->createChannel(['broadcast' => Bool, 'megagroup' => Bool, 'title' => 'string', 'about' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.createChannel`

Parameters:

broadcast - Json encoded Bool

megagroup - Json encoded Bool

title - Json encoded string

about - Json encoded string




Or, if you're into Lua:

```
Updates = channels.createChannel({broadcast=Bool, megagroup=Bool, title='string', about='string', })
```

### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_TITLE_EMPTY|No chat title provided|
|USER_RESTRICTED|You're spamreported, you can't create channels or chats.|


