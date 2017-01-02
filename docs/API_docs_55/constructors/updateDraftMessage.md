---
title: updateDraftMessage
description: updateDraftMessage attributes, type and example
---
## Constructor: updateDraftMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[Peer](../types/Peer.md) | Required|
|draft|[DraftMessage](../types/DraftMessage.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDraftMessage = ['_' => 'updateDraftMessage', 'peer' => Peer, 'draft' => DraftMessage, ];
```