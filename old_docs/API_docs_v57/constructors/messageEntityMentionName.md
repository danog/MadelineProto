---
title: messageEntityMentionName
description: messageEntityMentionName attributes, type and example
---
## Constructor: messageEntityMentionName  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$messageEntityMentionName = ['_' => 'messageEntityMentionName', 'offset' => int, 'length' => int, 'user_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageEntityMentionName", "offset": int, "length": int, "user_id": int}
```


Or, if you're into Lua:  


```
messageEntityMentionName={_='messageEntityMentionName', offset=int, length=int, user_id=int}

```


