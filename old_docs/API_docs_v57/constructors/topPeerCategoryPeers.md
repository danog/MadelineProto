---
title: topPeerCategoryPeers
description: topPeerCategoryPeers attributes, type and example
---
## Constructor: topPeerCategoryPeers  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|category|[TopPeerCategory](../types/TopPeerCategory.md) | Yes|
|count|[int](../types/int.md) | Yes|
|peers|Array of [TopPeer](../types/TopPeer.md) | Yes|



### Type: [TopPeerCategoryPeers](../types/TopPeerCategoryPeers.md)


### Example:

```
$topPeerCategoryPeers = ['_' => 'topPeerCategoryPeers', 'category' => TopPeerCategory, 'count' => int, 'peers' => [TopPeer]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "topPeerCategoryPeers", "category": TopPeerCategory, "count": int, "peers": [TopPeer]}
```


Or, if you're into Lua:  


```
topPeerCategoryPeers={_='topPeerCategoryPeers', category=TopPeerCategory, count=int, peers={TopPeer}}

```


