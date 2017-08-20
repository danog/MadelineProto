---
title: updateUserStatus
description: User went online/offline
---
## Constructor: updateUserStatus  
[Back to constructors index](index.md)



User went online/offline

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|status|[UserStatus](../types/UserStatus.md) | Yes|New user status|



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


