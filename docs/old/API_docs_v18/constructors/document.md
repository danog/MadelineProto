---
title: document
description: document attributes, type and example
---
## Constructor: document  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|date|[int](../types/int.md) | Required|
|file\_name|[string](../types/string.md) | Required|
|mime\_type|[string](../types/string.md) | Required|
|size|[int](../types/int.md) | Required|
|thumb|[PhotoSize](../types/PhotoSize.md) | Required|
|dc\_id|[int](../types/int.md) | Required|



### Type: [Document](../types/Document.md)


### Example:

```
$document = ['_' => 'document', 'id' => long, 'access_hash' => long, 'user_id' => int, 'date' => int, 'file_name' => string, 'mime_type' => string, 'size' => int, 'thumb' => PhotoSize, 'dc_id' => int, ];
```  

