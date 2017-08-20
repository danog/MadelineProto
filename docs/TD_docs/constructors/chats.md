---
title: chats
description: Contains list of chats
---
## Constructor: chats  
[Back to constructors index](index.md)



Contains list of chats

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chats|Array of [chat](../constructors/chat.md) | Yes|List of chats|



### Type: [Chats](../types/Chats.md)


### Example:

```
$chats = ['_' => 'chats', 'chats' => [chat]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chats", "chats": [chat]}
```


Or, if you're into Lua:  


```
chats={_='chats', chats={chat}}

```


