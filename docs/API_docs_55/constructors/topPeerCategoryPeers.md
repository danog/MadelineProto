---
title: topPeerCategoryPeers
description: topPeerCategoryPeers attributes, type and example
---
## Constructor: topPeerCategoryPeers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|category|[TopPeerCategory](../types/TopPeerCategory.md) | Required|
|count|[int](../types/int.md) | Required|
|peers|Array of [TopPeer](../types/TopPeer.md) | Required|



### Type: [TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)


### Example:

```
$topPeerCategoryPeers = ['_' => 'topPeerCategoryPeers', 'category' => TopPeerCategory, 'count' => int, 'peers' => [Vector t], ];
```