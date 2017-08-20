---
title: messagePinMessage
description: Some message was pinned
---
## Constructor: messagePinMessage  
[Back to constructors index](index.md)



Some message was pinned

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|message\_id|[long](../types/long.md) | Yes|Identifier of the pinned message, can be identifier of the deleted message|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messagePinMessage = ['_' => 'messagePinMessage', 'message_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messagePinMessage", "message_id": long}
```


Or, if you're into Lua:  


```
messagePinMessage={_='messagePinMessage', message_id=long}

```


