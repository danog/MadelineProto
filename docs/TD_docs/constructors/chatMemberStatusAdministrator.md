---
title: chatMemberStatusAdministrator
description: User is a chat member with some additional priviledges. In groups, administrators can edit and delete other messages, add new members and ban unpriviledged members
---
## Constructor: chatMemberStatusAdministrator  
[Back to constructors index](index.md)



User is a chat member with some additional priviledges. In groups, administrators can edit and delete other messages, add new members and ban unpriviledged members

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|can\_be\_edited|[Bool](../types/Bool.md) | Yes|True, if current user has rights to edit administrator privileges of that user|
|can\_change\_info|[Bool](../types/Bool.md) | Yes|True, if the administrator can change chat title, photo and other settings|
|can\_post\_messages|[Bool](../types/Bool.md) | Yes|True, if the administrator can create channel posts, broadcast channels only|
|can\_edit\_messages|[Bool](../types/Bool.md) | Yes|True, if the administrator can edit messages of other users, broadcast channels only|
|can\_delete\_messages|[Bool](../types/Bool.md) | Yes|True, if the administrator can delete messages of other users|
|can\_invite\_users|[Bool](../types/Bool.md) | Yes|True, if the administrator can invite new users to the chat|
|can\_restrict\_members|[Bool](../types/Bool.md) | Yes|True, if the administrator can restrict, ban or unban chat members|
|can\_pin\_messages|[Bool](../types/Bool.md) | Yes|True, if the administrator can pin messages, supergroup channels only|
|can\_promote\_members|[Bool](../types/Bool.md) | Yes|True, if the administrator can add new administrators with a subset of his own privileges or demote administrators directly or indirectly promoted by him|



### Type: [ChatMemberStatus](../types/ChatMemberStatus.md)


