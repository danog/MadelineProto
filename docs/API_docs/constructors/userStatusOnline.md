---
title: userStatusOnline
description: User is online
---
## Constructor: userStatusOnline  
[Back to constructors index](index.md)



User is online

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|expires|[int](../types/int.md) | Yes|Unix time when user's online status will expire|



### Type: [UserStatus](../types/UserStatus.md)


### Example:

```
$userStatusOnline = ['_' => 'userStatusOnline', 'expires' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "userStatusOnline", "expires": int}
```


Or, if you're into Lua:  


```
userStatusOnline={_='userStatusOnline', expires=int}

```


