---
title: chatMembers
description: Contains list of chat members
---
## Constructor: chatMembers  
[Back to constructors index](index.md)



Contains list of chat members

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|total\_count|[int](../types/int.md) | Yes|Approximate total count of found chat members|
|members|Array of [chatMember](../constructors/chatMember.md) | Yes|List of members|



### Type: [ChatMembers](../types/ChatMembers.md)


### Example:

```
$chatMembers = ['_' => 'chatMembers', 'total_count' => int, 'members' => [chatMember]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatMembers", "total_count": int, "members": [chatMember]}
```


Or, if you're into Lua:  


```
chatMembers={_='chatMembers', total_count=int, members={chatMember}}

```


