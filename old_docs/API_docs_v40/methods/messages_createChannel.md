---
title: messages.createChannel
description: Create channel
---
## Method: messages.createChannel  
[Back to methods index](index.md)


Create channel

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[CLICK ME string](../types/string.md) | Yes|Channel/supergroup title|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


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

$Updates = $MadelineProto->messages->createChannel(['title' => 'string', ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.createChannel
* params - `{"title": "string", }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.createChannel`

Parameters:

title - Json encoded string




Or, if you're into Lua:

```
Updates = messages.createChannel({title='string', })
```

