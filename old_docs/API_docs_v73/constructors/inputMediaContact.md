---
title: inputMediaContact
description: inputMediaContact attributes, type and example
---
## Constructor: inputMediaContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_number|[string](../types/string.md) | Yes|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaContact = ['_' => 'inputMediaContact', 'phone_number' => 'string', 'first_name' => 'string', 'last_name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaContact", "phone_number": "string", "first_name": "string", "last_name": "string"}
```


Or, if you're into Lua:  


```
inputMediaContact={_='inputMediaContact', phone_number='string', first_name='string', last_name='string'}

```


