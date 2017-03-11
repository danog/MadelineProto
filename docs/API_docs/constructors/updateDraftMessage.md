---
title: updateDraftMessage
description: updateDraftMessage attributes, type and example
---
## Constructor: updateDraftMessage  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[Peer](../types/Peer.md) | Yes|
|draft|[DraftMessage](../types/DraftMessage.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDraftMessage = ['_' => 'updateDraftMessage', 'peer' => Peer, 'draft' => DraftMessage, ];
```  

Or, if you're into Lua:  


```
updateDraftMessage={_='updateDraftMessage', peer=Peer, draft=DraftMessage, }

```


