---
title: updateDeleteMessages
description: Some messages was deleted
---
## Constructor: updateDeleteMessages  
[Back to constructors index](index.md)



Some messages was deleted

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|message\_ids|Array of [long](../constructors/long.md) | Yes|Identifiers of deleted message|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDeleteMessages = ['_' => 'updateDeleteMessages', 'chat_id' => long, 'message_ids' => [long]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateDeleteMessages", "chat_id": long, "message_ids": [long]}
```


Or, if you're into Lua:  


```
updateDeleteMessages={_='updateDeleteMessages', chat_id=long, message_ids={long}}

```


