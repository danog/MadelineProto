---
title: inputPeerNotifySettings
description: inputPeerNotifySettings attributes, type and example
---
## Constructor: inputPeerNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|show\_previews|[Bool](../types/Bool.md) | Optional|
|silent|[Bool](../types/Bool.md) | Optional|
|mute\_until|[int](../types/int.md) | Yes|
|sound|[string](../types/string.md) | Yes|



### Type: [InputPeerNotifySettings](../types/InputPeerNotifySettings.md)


### Example:

```
$inputPeerNotifySettings = ['_' => 'inputPeerNotifySettings', 'show_previews' => Bool, 'silent' => Bool, 'mute_until' => int, 'sound' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputPeerNotifySettings", "show_previews": Bool, "silent": Bool, "mute_until": int, "sound": "string"}
```


Or, if you're into Lua:  


```
inputPeerNotifySettings={_='inputPeerNotifySettings', show_previews=Bool, silent=Bool, mute_until=int, sound='string'}

```


