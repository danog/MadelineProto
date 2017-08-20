---
title: document
description: document attributes, type and example
---
## Constructor: document  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[long](../types/long.md) | Yes|
|access\_hash|[long](../types/long.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|file\_name|[string](../types/string.md) | Yes|
|mime\_type|[string](../types/string.md) | Yes|
|size|[int](../types/int.md) | Yes|
|thumb|[PhotoSize](../types/PhotoSize.md) | Yes|
|dc\_id|[int](../types/int.md) | Yes|



### Type: [Document](../types/Document.md)


### Example:

```
$document = ['_' => 'document', 'id' => long, 'access_hash' => long, 'user_id' => int, 'date' => int, 'file_name' => 'string', 'mime_type' => 'string', 'size' => int, 'thumb' => PhotoSize, 'dc_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "document", "id": long, "access_hash": long, "user_id": int, "date": int, "file_name": "string", "mime_type": "string", "size": int, "thumb": PhotoSize, "dc_id": int}
```


Or, if you're into Lua:  


```
document={_='document', id=long, access_hash=long, user_id=int, date=int, file_name='string', mime_type='string', size=int, thumb=PhotoSize, dc_id=int}

```


