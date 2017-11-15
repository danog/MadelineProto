---
title: updateUserStatus
description: updateUserStatus attributes, type and example
---
## Constructor: updateUserStatus  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|status|[UserStatus](../types/UserStatus.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUserStatus = ['_' => 'updateUserStatus', 'user_id' => int, 'status' => UserStatus];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateUserStatus", "user_id": int, "status": UserStatus}
```


Or, if you're into Lua:  


```
updateUserStatus={_='updateUserStatus', user_id=int, status=UserStatus}

```


