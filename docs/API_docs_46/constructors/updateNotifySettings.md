---
title: updateNotifySettings
description: updateNotifySettings attributes, type and example
---
## Constructor: updateNotifySettings  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|notify\_peer|[NotifyPeer](../types/NotifyPeer.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateNotifySettings = ['_' => 'updateNotifySettings', 'notify_peer' => NotifyPeer, 'notify_settings' => PeerNotifySettings, ];
```