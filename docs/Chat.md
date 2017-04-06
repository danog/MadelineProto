---
title: PWRTelegram chat
description: chat attributes, type and example
---
## Constructor: PWRTelegram chat  



### Attributes:

| Name     |    Type       | Required |Description|
|----------|:-------------:|:--------:|----------:|
|type|[string](../types/string.md) | Yes|bot, user, channel, supergroup or chat|
|id|[long](../types/long.md) | Yes|bot API chat id|
|access\_hash|[long](../types/long.md) | Yes|access hash of peer|
|first\_name|[string](../types/string.md) | Yes|First name of the user|
|last\_name|[string](../types/string.md) | Optional|Last name of the user|
|username|[string](../types/string.md) | Optional|Username|
|verified|[Bool](../types/Bool.md) | Optional|Is the peer official?|
|restricted|[Bool](../types/Bool.md) | Optional|Is the peer restricted to the current user?|
|restriction\_reason|[string](../types/string.md) | Optional|Restriction reason|
|status|[UserStatus](../types/UserStatus.md) | Optional|Describes last time user was online|
|bot\_inline\_placeholder|[string](../types/string.md) | Optional|Inline placeholder of inline bot|
|about|[string](../types/string.md) | Optional|Description of supergroups/channels or bios of users|
|bot\_info|[BotInfo](../types/BotInfo.md) | Optional|Bot info of bots|
|phone\_calls\_available|[Bool](../types/Bool.md) | Optional|Are phone calls available for that user?|
|phone\_calls\_private|[Bool](../types/Bool.md) | Optional||
|common\_chats\_count|[int](../types/int.md) | Yes|Number of chats in common with that user|
|photo|[string](../types/string.md) | Optional|bot API file id of the profile picture|
|title|[string](../types/string.md) | Optional|Chat title|
|participants\_count|[int](../types/int.md) | Optional|Number of participants in the chat.|
|kicked\_count|[int](../types/int.md) | Optional|Number of users kicked from the chat.|
|admin\_count|[int](../types/int.md) | Optional|Number of admins in the chat.|
|admin|[Bool](../types/Bool.md) | Optional|Are you an admin in this chat?|
|all\_members\_are\_administrators|[Bool](../types/Bool.md) | Optional|True if a group has ‘All Members Are Admins’ enabled.|
|invite|[string](../types/string.md) | Optional|Invite link of the chat|
|participants|Array of [Participant](Participant.md) | Yes|Chat participants|
|democracy|[Bool](../types/Bool.md) | Optional|Can everyone add users to this chat?|
|signatures|[Bool](../types/Bool.md) | Optional|Are channel signatures enabled?|
|can\_view\_participants|[Bool](../types/Bool.md) | Optional|Can you view participants (you can still view the bots in channels even if this is false)|
|can\_set\_username|[Bool](../types/Bool.md) | Optional|Can you set the username of this channel/supergroup?|
|migrated\_from\_chat\_id|[int](../types/int.md) | Optional|MTProto chat id of the original chat (render it negative to make it a bot API chat id)|
|migrated\_from\_max\_id|[int](../types/int.md) | Optional|Last message id before migration|
|pinned\_msg\_id|[int](../types/int.md) | Optional|Message id of pinned message|


