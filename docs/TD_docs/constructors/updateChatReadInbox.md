---
title: updateChatReadInbox
description: Some incoming messages was read
---
## Constructor: updateChatReadInbox  
[Back to constructors index](index.md)



Some incoming messages was read

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|last\_read\_inbox\_message\_id|[long](../types/long.md) | Yes|Identifier of last read incoming message|
|unread\_count|[int](../types/int.md) | Yes|Number of unread messages left in chat|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatReadInbox = ['_' => 'updateChatReadInbox', 'chat_id' => long, 'last_read_inbox_message_id' => long, 'unread_count' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatReadInbox", "chat_id": long, "last_read_inbox_message_id": long, "unread_count": int}
```


Or, if you're into Lua:  


```
updateChatReadInbox={_='updateChatReadInbox', chat_id=long, last_read_inbox_message_id=long, unread_count=int}

```


