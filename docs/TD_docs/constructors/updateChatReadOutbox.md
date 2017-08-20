---
title: updateChatReadOutbox
description: Some outcoming messages was read
---
## Constructor: updateChatReadOutbox  
[Back to constructors index](index.md)



Some outcoming messages was read

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|last\_read\_outbox\_message\_id|[long](../types/long.md) | Yes|Identifier of last read outgoing message|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatReadOutbox = ['_' => 'updateChatReadOutbox', 'chat_id' => long, 'last_read_outbox_message_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatReadOutbox", "chat_id": long, "last_read_outbox_message_id": long}
```


Or, if you're into Lua:  


```
updateChatReadOutbox={_='updateChatReadOutbox', chat_id=long, last_read_outbox_message_id=long}

```


