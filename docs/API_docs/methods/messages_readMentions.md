---
title: messages.readMentions
description: Mark mentions as read
---
## Method: messages.readMentions  
[Back to methods index](index.md)


Mark mentions as read

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|peer|[Username, chat ID, Update, Message or InputPeer](../types/InputPeer.md) | Optional|The chat|


### Return type: [messages\_AffectedHistory](../types/messages_AffectedHistory.md)

### Can bots use this method: **YES**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_AffectedHistory = $MadelineProto->messages->readMentions(['peer' => InputPeer, ]);
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):

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

