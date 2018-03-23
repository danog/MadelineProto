---
title: channels.getAdminedPublicChannels
description: Get all supergroups/channels where you're admin
---
## Method: channels.getAdminedPublicChannels  
[Back to methods index](index.md)


Get all supergroups/channels where you're admin



### Return type: [messages\_Chats](../types/messages_Chats.md)

### Can bots use this method: **NO**


### MadelineProto Example:


```
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->start();

$messages_Chats = $MadelineProto->channels->getAdminedPublicChannels();
```

### [PWRTelegram HTTP API](https://pwrtelegram.xyz) example (NOT FOR MadelineProto):



### As a user:

POST/GET to `https://api.pwrtelegram.xyz/userTOKEN/channels.getAdminedPublicChannels`

Parameters:




Or, if you're into Lua:

```
messages_Chats = channels.getAdminedPublicChannels({})
```

