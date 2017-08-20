---
title: updateNotifySettings
description: updateNotifySettings attributes, type and example
---
## Constructor: updateNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|notify\_peer|[NotifyPeer](../types/NotifyPeer.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNotifySettings = ['_' => 'updateNotifySettings', 'notify_peer' => NotifyPeer, 'notify_settings' => PeerNotifySettings];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNotifySettings", "notify_peer": NotifyPeer, "notify_settings": PeerNotifySettings}
```


Or, if you're into Lua:  


```
updateNotifySettings={_='updateNotifySettings', notify_peer=NotifyPeer, notify_settings=PeerNotifySettings}

```


