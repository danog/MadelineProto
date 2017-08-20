---
title: decryptedMessageActionSetMessageTTL
description: decryptedMessageActionSetMessageTTL attributes, type and example
---
## Constructor: decryptedMessageActionSetMessageTTL\_8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|ttl\_seconds|[int](../types/int.md) | Yes|



### Type: [DecryptedMessageAction](../types/DecryptedMessageAction.md)


### Example:

```
$decryptedMessageActionSetMessageTTL_8 = ['_' => 'decryptedMessageActionSetMessageTTL', 'ttl_seconds' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageActionSetMessageTTL", "ttl_seconds": int}
```


Or, if you're into Lua:  


```
decryptedMessageActionSetMessageTTL_8={_='decryptedMessageActionSetMessageTTL', ttl_seconds=int}

```


