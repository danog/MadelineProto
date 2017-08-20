---
title: updateEncryptedChatTyping
description: updateEncryptedChatTyping attributes, type and example
---
## Constructor: updateEncryptedChatTyping  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|chat\_id|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateEncryptedChatTyping = ['_' => 'updateEncryptedChatTyping', 'chat_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateEncryptedChatTyping", "chat_id": int}
```


Or, if you're into Lua:  


```
updateEncryptedChatTyping={_='updateEncryptedChatTyping', chat_id=int}

```


