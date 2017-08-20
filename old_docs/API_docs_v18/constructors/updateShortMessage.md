---
title: updateShortMessage
description: updateShortMessage attributes, type and example
---
## Constructor: updateShortMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[int](../types/int.md) | Yes|
|from\_id|[int](../types/int.md) | Yes|
|message|[string](../types/string.md) | Yes|
|pts|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|seq|[int](../types/int.md) | Yes|



### Type: [Updates](../types/Updates.md)


### Example:

```
$updateShortMessage = ['_' => 'updateShortMessage', 'id' => int, 'from_id' => int, 'message' => 'string', 'pts' => int, 'date' => int, 'seq' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateShortMessage", "id": int, "from_id": int, "message": "string", "pts": int, "date": int, "seq": int}
```


Or, if you're into Lua:  


```
updateShortMessage={_='updateShortMessage', id=int, from_id=int, message='string', pts=int, date=int, seq=int}

```


