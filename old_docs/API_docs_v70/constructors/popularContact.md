---
title: popularContact
description: popularContact attributes, type and example
---
## Constructor: popularContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|client\_id|[long](../types/long.md) | Yes|
|importers|[int](../types/int.md) | Yes|



### Type: [PopularContact](../types/PopularContact.md)


### Example:

```
$popularContact = ['_' => 'popularContact', 'client_id' => long, 'importers' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "popularContact", "client_id": long, "importers": int}
```


Or, if you're into Lua:  


```
popularContact={_='popularContact', client_id=long, importers=int}

```


