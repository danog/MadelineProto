---
title: messageEntityMentionName
description: Mention of the user by some text
---
## Constructor: messageEntityMentionName  
[Back to constructors index](index.md)



Mention of the user by some text

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|offset|[int](../types/int.md) | Yes|Offset of the entity in UTF-16 code points|
|length|[int](../types/int.md) | Yes|Length of the entity in UTF-16 code points|
|user\_id|[int](../types/int.md) | Yes|Identifier of the mentioned user|



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


