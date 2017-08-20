---
title: peerNotifySettings
description: peerNotifySettings attributes, type and example
---
## Constructor: peerNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|show\_previews|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|mute\_until|[int](../types/int.md) | Yes|
|sound|[string](../types/string.md) | Yes|



### Type: [PeerNotifySettings](../types/PeerNotifySettings.md)


### Example:

```
$peerNotifySettings = ['_' => 'peerNotifySettings', 'show_previews' => Bool, 'silent' => Bool, 'mute_until' => int, 'sound' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "peerNotifySettings", "show_previews": Bool, "silent": Bool, "mute_until": int, "sound": "string"}
```


Or, if you're into Lua:  


```
peerNotifySettings={_='peerNotifySettings', show_previews=Bool, silent=Bool, mute_until=int, sound='string'}

```


