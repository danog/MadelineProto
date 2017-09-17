---
title: chatMemberStatusRestricted
description: User has some additional restrictions in the chat. Unsupported in group chats and broadcast channels
---
## Constructor: chatMemberStatusRestricted  
[Back to constructors index](index.md)



User has some additional restrictions in the chat. Unsupported in group chats and broadcast channels

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|is\_member|[Bool](../types/Bool.md) | Yes|True, if user is chat member|
|restricted\_until\_date|[int](../types/int.md) | Yes|Date when the user will be unrestricted, 0 if never. Unix time. If user is restricted for more than 366 days or less than 30 seconds from the current time it considered to be restricted forever|
|can\_send\_messages|[Bool](../types/Bool.md) | Yes|True, if the user can send text messages, contacts, locations and venues|
|can\_send\_media\_messages|[Bool](../types/Bool.md) | Yes|True, if the user can send audios, documents, photos, videos, video notes and voice notes, implies can_send_messages|
|can\_send\_other\_messages|[Bool](../types/Bool.md) | Yes|True, if the user can send animations, games, stickers and use inline bots, implies can_send_media_messages|
|can\_add\_web\_page\_previews|[Bool](../types/Bool.md) | Yes|True, if user may add web page preview to his messages, implies can_send_messages|



### Type: [ChatMemberStatus](../types/ChatMemberStatus.md)


