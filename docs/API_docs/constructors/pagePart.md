---
title: pagePart
description: pagePart attributes, type and example
---
## Constructor: pagePart  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|blocks|Array of [PageBlock](../types/PageBlock.md) | Required|
|photos|Array of [Photo](../types/Photo.md) | Required|
|videos|Array of [Document](../types/Document.md) | Required|



### Type: [Page](../types/Page.md)


### Example:

```
$pagePart = ['_' => 'pagePart', 'blocks' => [Vector t], 'photos' => [Vector t], 'videos' => [Vector t], ];
```  

