---
title: inputPeerNotifySettings
description: inputPeerNotifySettings attributes, type and example
---
## Constructor: inputPeerNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|mute\_until|[int](../types/int.md) | Required|
|sound|[string](../types/string.md) | Required|
|show\_previews|[Bool](../types/Bool.md) | Required|
|events\_mask|[int](../types/int.md) | Required|



### Type: [InputPeerNotifySettings](../types/InputPeerNotifySettings.md)


### Example:

```
$inputPeerNotifySettings = ['_' => 'inputPeerNotifySettings', 'mute_until' => int, 'sound' => string, 'show_previews' => Bool, 'events_mask' => int, ];
```