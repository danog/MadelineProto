---
title: inputMessageEntityMentionName
description: inputMessageEntityMentionName attributes, type and example
---
## Constructor: inputMessageEntityMentionName  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|offset|[int](../types/int.md) | Yes|
|length|[int](../types/int.md) | Yes|
|user\_id|[InputUser](../types/InputUser.md) | Yes|



### Type: [MessageEntity](../types/MessageEntity.md)


### Example:

```
$inputMessageEntityMentionName = ['_' => 'inputMessageEntityMentionName', 'offset' => int, 'length' => int, 'user_id' => InputUser];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMessageEntityMentionName", "offset": int, "length": int, "user_id": InputUser}
```


Or, if you're into Lua:  


```
inputMessageEntityMentionName={_='inputMessageEntityMentionName', offset=int, length=int, user_id=InputUser}

```


