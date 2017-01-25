---
title: pageBlockEmbedPost
description: pageBlockEmbedPost attributes, type and example
---
## Constructor: pageBlockEmbedPost  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|url|[string](../types/string.md) | Required|
|webpage\_id|[long](../types/long.md) | Required|
|author\_photo\_id|[long](../types/long.md) | Required|
|author|[string](../types/string.md) | Required|
|date|[int](../types/int.md) | Required|
|blocks|Array of [PageBlock](../types/PageBlock.md) | Required|
|caption|[RichText](../types/RichText.md) | Required|



### Type: [PageBlock](../types/PageBlock.md)


### Example:

```
$pageBlockEmbedPost = ['_' => 'pageBlockEmbedPost', 'url' => string, 'webpage_id' => long, 'author_photo_id' => long, 'author' => string, 'date' => int, 'blocks' => [Vector t], 'caption' => RichText, ];
```  

