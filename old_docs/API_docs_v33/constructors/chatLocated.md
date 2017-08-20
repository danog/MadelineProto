---
title: chatLocated
description: chatLocated attributes, type and example
---
## Constructor: chatLocated  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|distance|[int](../types/int.md) | Yes|



### Type: [ChatLocated](../types/ChatLocated.md)


### Example:

```
$chatLocated = ['_' => 'chatLocated', 'chat_id' => int, 'distance' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatLocated", "chat_id": int, "distance": int}
```


Or, if you're into Lua:  


```
chatLocated={_='chatLocated', chat_id=int, distance=int}

```


