---
title: chatInviteLinkInfo
description: Contains information about chat invite link
---
## Constructor: chatInviteLinkInfo  
[Back to constructors index](index.md)



Contains information about chat invite link

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier of the invite link or 0 if user is not a member of this chat|
|title|[string](../types/string.md) | Yes|Title of the chat|
|photo|[chatPhoto](../types/chatPhoto.md) | Yes|Chat photo, nullable|
|member\_count|[int](../types/int.md) | Yes|Total member count|
|member\_user\_ids|Array of [int](../constructors/int.md) | Yes|User identifiers of some chat members that may be known to the current user|
|is\_group|[Bool](../types/Bool.md) | Yes|True, if the chat is a group chat|
|is\_channel|[Bool](../types/Bool.md) | Yes|True, if the chat is a channel chat|
|is\_public\_channel|[Bool](../types/Bool.md) | Yes|True, if the chat is a channel chat with set up username|
|is\_supergroup\_channel|[Bool](../types/Bool.md) | Yes|True, if the chat is a supergroup channel chat|



### Type: [ChatInviteLinkInfo](../types/ChatInviteLinkInfo.md)


