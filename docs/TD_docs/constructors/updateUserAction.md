---
title: updateUserAction
description: Some chat activity
---
## Constructor: updateUserAction  
[Back to constructors index](index.md)



Some chat activity

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|user\_id|[int](../types/int.md) | Yes|Identifier of user doing action|
|action|[SendMessageAction](../types/SendMessageAction.md) | Yes|Action description|



### Type: [Update](../types/Update.md)


### Example:

```
$updateUserAction = ['_' => 'updateUserAction', 'chat_id' => long, 'user_id' => int, 'action' => SendMessageAction];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateUserAction", "chat_id": long, "user_id": int, "action": SendMessageAction}
```


Or, if you're into Lua:  


```
updateUserAction={_='updateUserAction', chat_id=long, user_id=int, action=SendMessageAction}

```


