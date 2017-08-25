---
title: chatMemberStatusBanned
description: User was banned (and obviously is not a chat member) and can't return to the chat or view messages
---
## Constructor: chatMemberStatusBanned  
[Back to constructors index](index.md)



User was banned (and obviously is not a chat member) and can't return to the chat or view messages

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|banned\_until\_date|[int](../types/int.md) | Yes|Date when the user will be unbanned, 0 if never. Unix time. If user is banned for more than 366 days or less than 30 seconds from the current time it considered to be banned forever|



### Type: [ChatMemberStatus](../types/ChatMemberStatus.md)


