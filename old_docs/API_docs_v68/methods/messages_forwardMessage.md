---
title: messages.forwardMessage
description: messages.forwardMessage parameters, return type and example
---
## Method: messages.forwardMessage  
[Back to methods index](index.md)


### Parameters:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[InputPeer](../types/InputPeer.md) | Optional|
|id|[int](../types/int.md) | Yes|


### Return type: [Updates](../types/Updates.md)

### Can bots use this method: **YES**


### Errors this method can return:

| Error    | Description   |
|----------|---------------|
|CHAT_ID_INVALID|The provided chat id is invalid|
|MESSAGE_ID_INVALID|The provided message id is invalid|
|PEER_ID_INVALID|The provided peer id is invalid|
|YOU_BLOCKED_USER|You blocked this user|


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

$Updates = $MadelineProto->messages->forwardMessage(['peer' => InputPeer, 'id' => int, ]);
```

Or, if you're using the [PWRTelegram HTTP API](https://pwrtelegram.xyz):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.forwardMessage
* params - `{"peer": InputPeer, "id": int, }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.forwardMessage`

Parameters:

peer - Json encoded InputPeer

id - Json encoded int




Or, if you're into Lua:

```
Updates = messages.forwardMessage({peer=InputPeer, id=int, })
```

