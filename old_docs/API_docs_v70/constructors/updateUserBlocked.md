---
title: updateUserBlocked
description: updateUserBlocked attributes, type and example
---
## Constructor: updateUserBlocked  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|blocked|[Bool](../types/Bool.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUserBlocked = ['_' => 'updateUserBlocked', 'user_id' => int, 'blocked' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateUserBlocked", "user_id": int, "blocked": Bool}
```


Or, if you're into Lua:  


```
updateUserBlocked={_='updateUserBlocked', user_id=int, blocked=Bool}

```


