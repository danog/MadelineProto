---
title: updateDialogPinned
description: updateDialogPinned attributes, type and example
---
## Constructor: updateDialogPinned  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|pinned|[Bool](../types/Bool.md) | Optional|
|peer|[Peer](../types/Peer.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateDialogPinned = ['_' => 'updateDialogPinned', 'pinned' => Bool, 'peer' => Peer];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateDialogPinned", "pinned": Bool, "peer": Peer}
```


Or, if you're into Lua:  


```
updateDialogPinned={_='updateDialogPinned', pinned=Bool, peer=Peer}

```


