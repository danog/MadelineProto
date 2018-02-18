---
title: messageMediaContact
description: messageMediaContact attributes, type and example
---
## Constructor: messageMediaContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_number|[string](../types/string.md) | Yes|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaContact = ['_' => 'messageMediaContact', 'phone_number' => 'string', 'first_name' => 'string', 'last_name' => 'string', 'user_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaContact", "phone_number": "string", "first_name": "string", "last_name": "string", "user_id": int}
```


Or, if you're into Lua:  


```
messageMediaContact={_='messageMediaContact', phone_number='string', first_name='string', last_name='string', user_id=int}

```


