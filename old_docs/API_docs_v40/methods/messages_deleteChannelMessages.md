---
title: messages.deleteChannelMessages
description: Delete channel messages
---
## Method: messages.deleteChannelMessages  
[Back to methods index](index.md)


Delete channel messages

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The channel/supergroup|
|id|Array of [int](../types/int.md) | Yes|The IDs of messages to delete|


### Return type: [messages\_AffectedMessages](../types/messages_AffectedMessages.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_AffectedMessages = $MadelineProto->messages->deleteChannelMessages(['peer' => InputPeer, 'id' => [int, int], ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

### As a bot:

POST/GET to `https://api.pwrtelegram.xyz/botTOKEN/madeline`

Parameters:

* method - messages.deleteChannelMessages
* params - `{"peer": InputPeer, "id": [int], }`



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/messages.deleteChannelMessages`

Parameters:

peer - Json encoded InputPeer

id - Json encoded  array of int




Or, if you're into Lua:

```
messages_AffectedMessages = messages.deleteChannelMessages({peer=InputPeer, id={int}, })
```

