---
title: Participant
description: PWRTelegram participant attributes, type and example
---
## Constructor: PWRTelegram chat participant  



### Attributes:

| Name     |    Type       | Required | Description|
|----------|---------------|----------|------------|
|user|[Chat](Chat.md) | Yes| The participant|
|inviter|[Chat](Chat.md) | Optional|The user that invited this participant|
|promoted_by|[Chat](Chat.md) | Optional|The user that promoted this participant|
|kicked_by|[Chat](Chat.md) | Optional|The user that kicked this participant|
|date|[int](API_docs/types/int.md) | Yes|When was the user invited|
|role|[string](API_docs/types/string.md) | Yes|user, admin, creator, banned|
|can_edit|[Bool](API_docs/types/Bool.md) | Optional|Can the user edit messages in the channel|
|left|[Bool](API_docs/types/Bool.md) | Optional|Has this user left|
|admin_rights|[ChannelAdminRights](API_docs/types/ChannelAdminRights.md) | Optional|Admin rights|
|banned_rights|[ChannelBannedRights](API_docs/types/ChannelBannedRights.md) | Optional|Banned rights|
