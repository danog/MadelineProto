---
title: peerNotifySettings
description: peerNotifySettings attributes, type and example
---
## Constructor: peerNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|mute\_until|[int](../types/int.md) | Yes|
|sound|[string](../types/string.md) | Yes|
|show\_previews|[Bool](../types/Bool.md) | Yes|
|events\_mask|[int](../types/int.md) | Yes|



### Type: [PeerNotifySettings](../types/PeerNotifySettings.md)


### Example:

```
$peerNotifySettings = ['_' => 'peerNotifySettings', 'mute_until' => int, 'sound' => 'string', 'show_previews' => Bool, 'events_mask' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "peerNotifySettings", "mute_until": int, "sound": "string", "show_previews": Bool, "events_mask": int}
```


Or, if you're into Lua:  


```
peerNotifySettings={_='peerNotifySettings', mute_until=int, sound='string', show_previews=Bool, events_mask=int}

```


