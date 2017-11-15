---
title: recentMeUrlChat
description: recentMeUrlChat attributes, type and example
---
## Constructor: recentMeUrlChat  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|chat\_id|[int](../types/int.md) | Yes|



### Type: [RecentMeUrl](../types/RecentMeUrl.md)


### Example:

```
$recentMeUrlChat = ['_' => 'recentMeUrlChat', 'url' => 'string', 'chat_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "recentMeUrlChat", "url": "string", "chat_id": int}
```


Or, if you're into Lua:  


```
recentMeUrlChat={_='recentMeUrlChat', url='string', chat_id=int}

```


