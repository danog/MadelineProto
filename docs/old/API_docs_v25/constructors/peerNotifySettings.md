---
title: peerNotifySettings
description: peerNotifySettings attributes, type and example
---
## Constructor: peerNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|mute\_until|[int](../types/int.md) | Required|
|sound|[string](../types/string.md) | Required|
|show\_previews|[Bool](../types/Bool.md) | Required|
|events\_mask|[int](../types/int.md) | Required|



### Type: [PeerNotifySettings](../types/PeerNotifySettings.md)


### Example:

```
$peerNotifySettings = ['_' => 'peerNotifySettings', 'mute_until' => int, 'sound' => string, 'show_previews' => Bool, 'events_mask' => int, ];
```  

