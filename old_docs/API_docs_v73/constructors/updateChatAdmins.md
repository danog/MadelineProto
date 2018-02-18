---
title: updateChatAdmins
description: updateChatAdmins attributes, type and example
---
## Constructor: updateChatAdmins  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|
|enabled|[Bool](../types/Bool.md) | Yes|
|version|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatAdmins = ['_' => 'updateChatAdmins', 'chat_id' => int, 'enabled' => Bool, 'version' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatAdmins", "chat_id": int, "enabled": Bool, "version": int}
```


Or, if you're into Lua:  


```
updateChatAdmins={_='updateChatAdmins', chat_id=int, enabled=Bool, version=int}

```


