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
|channel|[channel](../types/channel.md) | Yes|General info about the channel|
|about|[string](../types/string.md) | Yes|Information about the channel|
|member\_count|[int](../types/int.md) | Yes|Channel member count, 0 if unknown|
|administrator\_count|[int](../types/int.md) | Yes|Number of privileged users in the channel, 0 if unknown|
|kicked\_count|[int](../types/int.md) | Yes|Number of users kicked from the channel, 0 if unknown|
|can\_get\_members|[Bool](../types/Bool.md) | Yes|True, if members of the channel can be retrieved|
|can\_set\_username|[Bool](../types/Bool.md) | Yes|True, if channel can be made public|
|invite\_link|[string](../types/string.md) | Yes|Invite link for this channel|
|pinned\_message\_id|[long](../types/long.md) | Yes|Identifier of the pinned message in the channel chat, or 0 if none|
|migrated\_from\_group\_id|[int](../types/int.md) | Yes|Identifier of the group, this supergroup migrated from, or 0 if none|
|migrated\_from\_max\_message\_id|[long](../types/long.md) | Yes|Identifier of last message in the group chat migrated from, or 0 if none|



### Type: [ChannelFull](../types/ChannelFull.md)


### Example:

```
$channelFull = ['_' => 'channelFull', 'channel' => channel, 'about' => 'string', 'member_count' => int, 'administrator_count' => int, 'kicked_count' => int, 'can_get_members' => Bool, 'can_set_username' => Bool, 'invite_link' => 'string', 'pinned_message_id' => long, 'migrated_from_group_id' => int, 'migrated_from_max_message_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelFull", "channel": channel, "about": "string", "member_count": int, "administrator_count": int, "kicked_count": int, "can_get_members": Bool, "can_set_username": Bool, "invite_link": "string", "pinned_message_id": long, "migrated_from_group_id": int, "migrated_from_max_message_id": long}
```


Or, if you're into Lua:  


```
channelFull={_='channelFull', channel=channel, about='string', member_count=int, administrator_count=int, kicked_count=int, can_get_members=Bool, can_set_username=Bool, invite_link='string', pinned_message_id=long, migrated_from_group_id=int, migrated_from_max_message_id=long}

```


