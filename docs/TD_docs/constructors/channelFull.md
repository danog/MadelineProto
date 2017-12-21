---
title: channelFull
description: Gives full information about a channel
---
## Constructor: channelFull  
[Back to constructors index](index.md)



Gives full information about a channel

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|description|[string](../types/string.md) | Yes|Channel description|
|member\_count|[int](../types/int.md) | Yes|Channel member count, 0 if unknown|
|administrator\_count|[int](../types/int.md) | Yes|Number of privileged users in the channel, 0 if unknown|
|restricted\_count|[int](../types/int.md) | Yes|Number of restricted users in the channel, 0 if unknown|
|banned\_count|[int](../types/int.md) | Yes|Number of users banned from the channel, 0 if unknown|
|can\_get\_members|[Bool](../types/Bool.md) | Yes|True, if members of the channel can be retrieved|
|can\_set\_username|[Bool](../types/Bool.md) | Yes|True, if the channel can be made public|
|can\_set\_sticker\_set|[Bool](../types/Bool.md) | Yes|True, if the channel sticker set can be changed|
|sticker\_set\_id|[int64](../constructors/int64.md) | Yes|Identifier of channel sticker set, or 0 if none|
|invite\_link|[string](../types/string.md) | Yes|Invite link for this channel|
|pinned\_message\_id|[int53](../types/int53.md) | Yes|Identifier of the pinned message in the channel chat, or 0 if none|
|migrated\_from\_group\_id|[int](../types/int.md) | Yes|Identifier of the group, this supergroup migrated from, or 0 if none|
|migrated\_from\_max\_message\_id|[int53](../types/int53.md) | Yes|Identifier of last message in the group chat migrated from, or 0 if none|



### Type: [ChannelFull](../types/ChannelFull.md)


