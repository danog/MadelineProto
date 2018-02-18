---
title: postAddress
description: postAddress attributes, type and example
---
## Constructor: postAddress  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|street\_line1|[string](../types/string.md) | Yes|
|street\_line2|[string](../types/string.md) | Yes|
|city|[string](../types/string.md) | Yes|
|state|[string](../types/string.md) | Yes|
|country\_iso2|[string](../types/string.md) | Yes|
|post\_code|[string](../types/string.md) | Yes|



### Type: [PostAddress](../types/PostAddress.md)


### Example:

```
$postAddress = ['_' => 'postAddress', 'street_line1' => 'string', 'street_line2' => 'string', 'city' => 'string', 'state' => 'string', 'country_iso2' => 'string', 'post_code' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "postAddress", "street_line1": "string", "street_line2": "string", "city": "string", "state": "string", "country_iso2": "string", "post_code": "string"}
```


Or, if you're into Lua:  


```
postAddress={_='postAddress', street_line1='string', street_line2='string', city='string', state='string', country_iso2='string', post_code='string'}

```


