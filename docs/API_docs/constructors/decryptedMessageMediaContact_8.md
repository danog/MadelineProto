---
title: decryptedMessageMediaContact
description: decryptedMessageMediaContact attributes, type and example
---
## Constructor: decryptedMessageMediaContact\_8  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_number|[string](../types/string.md) | Yes|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|



### Type: [DecryptedMessageMedia](../types/DecryptedMessageMedia.md)


### Example:

```
$decryptedMessageMediaContact_8 = ['_' => 'decryptedMessageMediaContact', 'phone_number' => 'string', 'first_name' => 'string', 'last_name' => 'string', 'user_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "decryptedMessageMediaContact", "phone_number": "string", "first_name": "string", "last_name": "string", "user_id": int}
```


Or, if you're into Lua:  


```
decryptedMessageMediaContact_8={_='decryptedMessageMediaContact', phone_number='string', first_name='string', last_name='string', user_id=int}

```


