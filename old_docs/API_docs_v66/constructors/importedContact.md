---
title: importedContact
description: importedContact attributes, type and example
---
## Constructor: importedContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|client\_id|[long](../types/long.md) | Yes|



### Type: [ImportedContact](../types/ImportedContact.md)


### Example:

```
$importedContact = ['_' => 'importedContact', 'user_id' => int, 'client_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "importedContact", "user_id": int, "client_id": long}
```


Or, if you're into Lua:  


```
importedContact={_='importedContact', user_id=int, client_id=long}

```


