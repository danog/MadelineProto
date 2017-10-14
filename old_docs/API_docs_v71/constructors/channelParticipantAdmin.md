---
title: channelParticipantAdmin
description: channelParticipantAdmin attributes, type and example
---
## Constructor: channelParticipantAdmin  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|can\_edit|[Bool](../types/Bool.md) | Optional|
|user\_id|[int](../types/int.md) | Yes|
|inviter\_id|[int](../types/int.md) | Yes|
|promoted\_by|[int](../types/int.md) | Yes|
|date|[int](../types/int.md) | Yes|
|admin\_rights|[ChannelAdminRights](../types/ChannelAdminRights.md) | Yes|



### Type: [ChannelParticipant](../types/ChannelParticipant.md)


### Example:

```
$channelParticipantAdmin = ['_' => 'channelParticipantAdmin', 'can_edit' => Bool, 'user_id' => int, 'inviter_id' => int, 'promoted_by' => int, 'date' => int, 'admin_rights' => ChannelAdminRights];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "channelParticipantAdmin", "can_edit": Bool, "user_id": int, "inviter_id": int, "promoted_by": int, "date": int, "admin_rights": ChannelAdminRights}
```


Or, if you're into Lua:  


```
channelParticipantAdmin={_='channelParticipantAdmin', can_edit=Bool, user_id=int, inviter_id=int, promoted_by=int, date=int, admin_rights=ChannelAdminRights}

```


