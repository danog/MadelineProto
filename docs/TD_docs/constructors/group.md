---
title: group
description: Represents a group of zero or more other users
---
## Constructor: group  
[Back to constructors index](index.md)



Represents a group of zero or more other users

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int](../types/int.md) | Yes|Group identifier|
|member\_count|[int](../types/int.md) | Yes|Group member count|
|status|[ChatMemberStatus](../types/ChatMemberStatus.md) | Yes|Status of the current user in the group|
|anyone\_can\_edit|[Bool](../types/Bool.md) | Yes|True, if all members granted editor rights in the group|
|is\_active|[Bool](../types/Bool.md) | Yes|True, if group is active|
|migrated\_to\_channel\_id|[int](../types/int.md) | Yes|Identifier of channel (supergroup) to which this group was migrated or 0 if none|



### Type: [Group](../types/Group.md)


### Example:

```
$group = ['_' => 'group', 'id' => int, 'member_count' => int, 'status' => ChatMemberStatus, 'anyone_can_edit' => Bool, 'is_active' => Bool, 'migrated_to_channel_id' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "group", "id": int, "member_count": int, "status": ChatMemberStatus, "anyone_can_edit": Bool, "is_active": Bool, "migrated_to_channel_id": int}
```


Or, if you're into Lua:  


```
group={_='group', id=int, member_count=int, status=ChatMemberStatus, anyone_can_edit=Bool, is_active=Bool, migrated_to_channel_id=int}

```


