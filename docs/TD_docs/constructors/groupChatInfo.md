---
title: groupChatInfo
description: Chat with zero or more other users
---
## Constructor: groupChatInfo  
[Back to constructors index](index.md)



Chat with zero or more other users

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|group|[group](../types/group.md) | Yes|Information about the chat|



### Type: [ChatInfo](../types/ChatInfo.md)


### Example:

```
$groupChatInfo = ['_' => 'groupChatInfo', 'group' => group];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "groupChatInfo", "group": group}
```


Or, if you're into Lua:  


```
groupChatInfo={_='groupChatInfo', group=group}

```


