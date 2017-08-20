---
title: updateNewMessage
description: updateNewMessage attributes, type and example
---
## Constructor: updateNewMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[Message](../types/Message.md) | Yes|
|pts|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNewMessage = ['_' => 'updateNewMessage', 'message' => Message, 'pts' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNewMessage", "message": Message, "pts": int}
```


Or, if you're into Lua:  


```
updateNewMessage={_='updateNewMessage', message=Message, pts=int}

```


