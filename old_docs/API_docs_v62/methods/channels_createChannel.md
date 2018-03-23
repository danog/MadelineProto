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
|broadcast|[CLICK ME Bool](../types/Bool.md) | Optional|Set this to true to create a channel|
|megagroup|[CLICK ME Bool](../types/Bool.md) | Optional|Set this to true to create a supergroup|
|title|[CLICK ME string](../types/string.md) | Yes|Supergroup/channel title|
|about|[CLICK ME string](../types/string.md) | Yes|About text|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **NO**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_TITLE_EMPTY|No chat title provided|
|USER_RESTRICTED|You're spamreported, you can't create channels or chats.|


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

