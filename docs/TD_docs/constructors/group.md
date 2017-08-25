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
|everyone\_is\_administrator|[Bool](../types/Bool.md) | Yes|True, if all members granted administrator rights in the group|
|is\_active|[Bool](../types/Bool.md) | Yes|True, if group is active|
|migrated\_to\_channel\_id|[int](../types/int.md) | Yes|Identifier of channel (supergroup) to which this group was migrated or 0 if none|



### Type: [Group](../types/Group.md)


