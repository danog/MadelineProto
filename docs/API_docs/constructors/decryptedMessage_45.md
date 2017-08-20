---
title: decryptedMessage
description: decryptedMessage attributes, type and example
---
## Constructor: decryptedMessage\_45  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ttl|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|media|[DecryptedMessageMedia](../types/DecryptedMessageMedia.md) | Optional|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|via\_bot\_name|[string](../types/string.md) | Optional|
|reply\_to\_random\_id|[long](../types/long.md) | Optional|



### Type: [DecryptedMessage](../types/DecryptedMessage.md)


### Example:

```
$decryptedMessage_45 = ['_' => 'decryptedMessage', 'ttl' => int, 'message' => 'string', 'media' => DecryptedMessageMedia, 'entities' => [MessageEntity], 'via_bot_name' => 'string', 'reply_to_random_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessage", "ttl": int, "message": "string", "media": DecryptedMessageMedia, "entities": [MessageEntity], "via_bot_name": "string", "reply_to_random_id": long}
```


Or, if you're into Lua:  


```
decryptedMessage_45={_='decryptedMessage', ttl=int, message='string', media=DecryptedMessageMedia, entities={MessageEntity}, via_bot_name='string', reply_to_random_id=long}

```


