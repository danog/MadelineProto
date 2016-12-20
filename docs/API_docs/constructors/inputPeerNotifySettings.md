---
title: inputPeerNotifySettings
description: inputPeerNotifySettings attributes, type and example
---
## Constructor: inputPeerNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|show\_previews|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|mute\_until|[int](../types/int.md) | Required|
|sound|[string](../types/string.md) | Required|



### Type: [InputPeerNotifySettings](../types/InputPeerNotifySettings.md)


### Example:

```
$inputPeerNotifySettings = ['_' => inputPeerNotifySettings', 'show_previews' => true, 'silent' => true, 'mute_until' => int, 'sound' => string, ];
```