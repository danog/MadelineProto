---
title: updateReadChannelInbox
description: updateReadChannelInbox attributes, type and example
---
## Constructor: updateReadChannelInbox  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[Peer](../types/Peer.md) | Required|
|max\_id|[int](../types/int.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateReadChannelInbox = ['_' => 'updateReadChannelInbox', 'peer' => Peer, 'max_id' => int, ];
```  

