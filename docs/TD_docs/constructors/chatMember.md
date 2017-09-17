---
title: chatMember
description: User with information about its chat joining/leaving
---
## Constructor: chatMember  
[Back to constructors index](index.md)



User with information about its chat joining/leaving

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier of the chat member|
|inviter\_user\_id|[int](../types/int.md) | Yes|Identifier of a user invited/promoted/banned this member in the chat, 0 if unknown|
|join\_date|[int](../types/int.md) | Yes|Date the user has joined a chat, unix time|
|status|[ChatMemberStatus](../types/ChatMemberStatus.md) | Yes|Status of the member in the chat|
|bot\_info|[botInfo](../types/botInfo.md) | Yes|Information about bot if user is a bot, nullable. Can be null even for bot if bot is not a chat member|



### Type: [ChatMember](../types/ChatMember.md)


