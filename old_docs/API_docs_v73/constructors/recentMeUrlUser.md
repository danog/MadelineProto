---
title: recentMeUrlUser
description: recentMeUrlUser attributes, type and example
---
## Constructor: recentMeUrlUser  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|url|[string](../types/string.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|



### Type: [RecentMeUrl](../types/RecentMeUrl.md)


### Example:

```
$recentMeUrlUser = ['_' => 'recentMeUrlUser', 'url' => 'string', 'user_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "recentMeUrlUser", "url": "string", "user_id": int}
```


Or, if you're into Lua:  


```
recentMeUrlUser={_='recentMeUrlUser', url='string', user_id=int}

```


