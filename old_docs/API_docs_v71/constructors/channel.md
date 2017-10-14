---
title: channel
description: channel attributes, type and example
---
## Constructor: channel  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|creator|[Bool](../types/Bool.md) | Optional|
|left|[Bool](../types/Bool.md) | Optional|
|editor|[Bool](../types/Bool.md) | Optional|
|broadcast|[Bool](../types/Bool.md) | Optional|
|verified|[Bool](../types/Bool.md) | Optional|
|megagroup|[Bool](../types/Bool.md) | Optional|
|restricted|[Bool](../types/Bool.md) | Optional|
|democracy|[Bool](../types/Bool.md) | Optional|
|signatures|[Bool](../types/Bool.md) | Optional|
|min|[Bool](../types/Bool.md) | Optional|
|id|[int](../types/int.md) | Yes|
|access\_hash|[long](../types/long.md) | Optional|
|title|[string](../types/string.md) | Yes|
|username|[string](../types/string.md) | Optional|
|photo|[ChatPhoto](../types/ChatPhoto.md) | Yes|
|date|[int](../types/int.md) | Yes|
|version|[int](../types/int.md) | Yes|
|restriction\_reason|[string](../types/string.md) | Optional|
|admin\_rights|[ChannelAdminRights](../types/ChannelAdminRights.md) | Optional|
|banned\_rights|[ChannelBannedRights](../types/ChannelBannedRights.md) | Optional|



### Type: [Chat](../types/Chat.md)


### Example:

```
$channel = ['_' => 'channel', 'creator' => Bool, 'left' => Bool, 'editor' => Bool, 'broadcast' => Bool, 'verified' => Bool, 'megagroup' => Bool, 'restricted' => Bool, 'democracy' => Bool, 'signatures' => Bool, 'min' => Bool, 'id' => int, 'access_hash' => long, 'title' => 'string', 'username' => 'string', 'photo' => ChatPhoto, 'date' => int, 'version' => int, 'restriction_reason' => 'string', 'admin_rights' => ChannelAdminRights, 'banned_rights' => ChannelBannedRights];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channel", "creator": Bool, "left": Bool, "editor": Bool, "broadcast": Bool, "verified": Bool, "megagroup": Bool, "restricted": Bool, "democracy": Bool, "signatures": Bool, "min": Bool, "id": int, "access_hash": long, "title": "string", "username": "string", "photo": ChatPhoto, "date": int, "version": int, "restriction_reason": "string", "admin_rights": ChannelAdminRights, "banned_rights": ChannelBannedRights}
```


Or, if you're into Lua:  


```
channel={_='channel', creator=Bool, left=Bool, editor=Bool, broadcast=Bool, verified=Bool, megagroup=Bool, restricted=Bool, democracy=Bool, signatures=Bool, min=Bool, id=int, access_hash=long, title='string', username='string', photo=ChatPhoto, date=int, version=int, restriction_reason='string', admin_rights=ChannelAdminRights, banned_rights=ChannelBannedRights}

```


