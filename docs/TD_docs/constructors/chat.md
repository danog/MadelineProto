---
title: chat
description: Chat (private chat or group chat or channel chat)
---
## Constructor: chat  
[Back to constructors index](index.md)



Chat (private chat or group chat or channel chat)

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int53](../types/int53.md) | Yes|Chat unique identifier|
|type|[ChatType](../types/ChatType.md) | Yes|Information about type of the chat|
|title|[string](../types/string.md) | Yes|Chat title|
|photo|[chatPhoto](../types/chatPhoto.md) | Yes|Chat photo, nullable|
|top\_message|[message](../types/message.md) | Yes|Last message in the chat, nullable|
|order|[long](../types/long.md) | Yes|Parameter by descending of which chats are sorted in the chat list. If order of two chats is equal, then they need to be sorted by id also in descending order. If order == 0, position of the chat in the list is undetermined|
|is\_pinned|[Bool](../types/Bool.md) | Yes|True, if the chat is pinned|
|unread\_count|[int](../types/int.md) | Yes|Count of unread messages in the chat|
|last\_read\_inbox\_message\_id|[int53](../types/int53.md) | Yes|Identifier of last read incoming message|
|last\_read\_outbox\_message\_id|[int53](../types/int53.md) | Yes|Identifier of last read outgoing message|
|notification\_settings|[notificationSettings](../types/notificationSettings.md) | Yes|Notification settings for this chat|
|reply\_markup\_message\_id|[int53](../types/int53.md) | Yes|Identifier of the message from which reply markup need to be used or 0 if there is no default custom reply markup in the chat|
|draft\_message|[draftMessage](../types/draftMessage.md) | Yes|Draft of a message in the chat, nullable. parse_mode in input_message_text always will be null|
|client\_data|[string](../types/string.md) | Yes|Client specified data, associated with the chat. For example, chat position or local chat notification settings may be stored here. Persistent if message db is used|



### Type: [Chat](../types/Chat.md)


