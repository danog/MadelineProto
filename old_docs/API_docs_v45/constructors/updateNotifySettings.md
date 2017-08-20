---
title: updateNotifySettings
description: updateNotifySettings attributes, type and example
---
## Constructor: updateNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|peer|[NotifyPeer](../types/NotifyPeer.md) | Yes|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNotifySettings = ['_' => 'updateNotifySettings', 'peer' => NotifyPeer, 'notify_settings' => PeerNotifySettings];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateNotifySettings", "peer": NotifyPeer, "notify_settings": PeerNotifySettings}
```


Or, if you're into Lua:  


```
updateNotifySettings={_='updateNotifySettings', peer=NotifyPeer, notify_settings=PeerNotifySettings}

```


