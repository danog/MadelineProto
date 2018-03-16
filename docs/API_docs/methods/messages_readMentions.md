---
title: messages.readMentions
description: messages.readMentions parameters, return type and example
---
## Method: messages.readMentions  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Optional|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

### Can bots use this method: **YES**


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

$messages_AffectedHistory = $MadelineProto->messages->readMentions(['peer' => InputPeer, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.readMentions
* params - `{"peer": InputPeer, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.readMentions`

Parameters:

peer - Json encoded InputPeer




Or, if you're into Lua:

```
messages_AffectedHistory = messages.readMentions({peer=InputPeer, })
```

