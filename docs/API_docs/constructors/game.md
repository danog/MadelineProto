## Constructor: game  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[long](../types/long.md) | Required|
|access\_hash|[long](../types/long.md) | Required|
|short\_name|[string](../types/string.md) | Required|
|title|[string](../types/string.md) | Required|
|description|[string](../types/string.md) | Required|
|photo|[Photo](../types/Photo.md) | Required|
|document|[Document](../types/Document.md) | Optional|
### Type: 

[Game](../types/Game.md)
### Example:

```
$game = ['_' => game', 'id' => long, 'access_hash' => long, 'short_name' => string, 'title' => string, 'description' => string, 'photo' => Photo, 'document' => Document, ];
```