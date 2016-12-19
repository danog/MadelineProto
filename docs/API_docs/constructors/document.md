## Constructor: document  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|date|[int](../types/int.md) | Required|
|mime\_type|[string](../types/string.md) | Required|
|size|[int](../types/int.md) | Required|
|thumb|[PhotoSize](../types/PhotoSize.md) | Required|
|dc\_id|[int](../types/int.md) | Required|
|version|[int](../types/int.md) | Required|
|attributes|Array of [DocumentAttribute](../types/DocumentAttribute.md) | Required|



### Type: [Document](../types/Document.md)


### Example:

```
$document = ['_' => document', 'id' => long, 'access_hash' => long, 'date' => int, 'mime_type' => string, 'size' => int, 'thumb' => PhotoSize, 'dc_id' => int, 'version' => int, 'attributes' => [Vector t], ];
```