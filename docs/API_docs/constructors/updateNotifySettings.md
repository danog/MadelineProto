## Constructor: updateNotifySettings  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|peer|[NotifyPeer](../types/NotifyPeer.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|


### Type: [Update](../types/Update.md)

### Example:


```
$updateNotifySettings = ['peer' => NotifyPeer, 'notify_settings' => PeerNotifySettings, ];
```