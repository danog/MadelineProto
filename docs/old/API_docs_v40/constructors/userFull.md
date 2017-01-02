---
title: userFull
description: userFull attributes, type and example
---
## Constructor: userFull  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user|[User](../types/User.md) | Required|
|link|[contacts\_Link](../types/contacts_Link.md) | Required|
|profile\_photo|[Photo](../types/Photo.md) | Required|
|notify\_settings|[PeerNotifySettings](../types/PeerNotifySettings.md) | Required|
|blocked|[Bool](../types/Bool.md) | Required|
|bot\_info|[BotInfo](../types/BotInfo.md) | Required|



### Type: [UserFull](../types/UserFull.md)


### Example:

```
$userFull = ['_' => 'userFull', 'user' => User, 'link' => contacts.Link, 'profile_photo' => Photo, 'notify_settings' => PeerNotifySettings, 'blocked' => Bool, 'bot_info' => BotInfo, ];
```  

