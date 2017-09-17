---
title: groupFull
description: Gives full information about a group
---
## Constructor: groupFull  
[Back to constructors index](index.md)



Gives full information about a group

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|creator\_user\_id|[int](../types/int.md) | Yes|User identifier of the group creator, 0 if unknown|
|members|Array of [chatMember](../constructors/chatMember.md) | Yes|Group members|
|invite\_link|[string](../types/string.md) | Yes|Invite link for this group, available only for group creator and only after it is generated at least once|



### Type: [GroupFull](../types/GroupFull.md)


