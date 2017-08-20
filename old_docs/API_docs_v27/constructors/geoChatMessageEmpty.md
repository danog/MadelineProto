---
title: geoChatMessageEmpty
description: geoChatMessageEmpty attributes, type and example
---
## Constructor: geoChatMessageEmpty  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|id|[int](../types/int.md) | Yes|



### Type: [GeoChatMessage](../types/GeoChatMessage.md)


### Example:

```
$geoChatMessageEmpty = ['_' => 'geoChatMessageEmpty', 'chat_id' => int, 'id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "geoChatMessageEmpty", "chat_id": int, "id": int}
```


Or, if you're into Lua:  


```
geoChatMessageEmpty={_='geoChatMessageEmpty', chat_id=int, id=int}

```


