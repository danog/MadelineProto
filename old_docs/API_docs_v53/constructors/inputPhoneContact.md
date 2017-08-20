---
title: inputPhoneContact
description: inputPhoneContact attributes, type and example
---
## Constructor: inputPhoneContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|client\_id|[long](../types/long.md) | Yes|
|phone|[string](../types/string.md) | Yes|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|



### Type: [InputContact](../types/InputContact.md)


### Example:

```
$inputPhoneContact = ['_' => 'inputPhoneContact', 'client_id' => long, 'phone' => 'string', 'first_name' => 'string', 'last_name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPhoneContact", "client_id": long, "phone": "string", "first_name": "string", "last_name": "string"}
```


Or, if you're into Lua:  


```
inputPhoneContact={_='inputPhoneContact', client_id=long, phone='string', first_name='string', last_name='string'}

```


