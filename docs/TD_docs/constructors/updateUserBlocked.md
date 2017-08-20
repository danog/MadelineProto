---
title: updateUserBlocked
description: User blocked/unblocked
---
## Constructor: updateUserBlocked  
[Back to constructors index](index.md)



User blocked/unblocked

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier|
|is\_blocked|[Bool](../types/Bool.md) | Yes|Is user blacklisted by current user|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUserBlocked = ['_' => 'updateUserBlocked', 'user_id' => int, 'is_blocked' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateUserBlocked", "user_id": int, "is_blocked": Bool}
```


Or, if you're into Lua:  


```
updateUserBlocked={_='updateUserBlocked', user_id=int, is_blocked=Bool}

```


