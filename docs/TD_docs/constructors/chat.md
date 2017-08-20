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
|id|[long](../types/long.md) | Yes|Chat unique identifier|
|title|[string](../types/string.md) | Yes|Chat title|
|photo|[chatPhoto](../types/chatPhoto.md) | Yes|Chat photo, nullable|
|top\_message|[message](../types/message.md) | Yes|Last message in the chat, nullable|
|order|[long](../types/long.md) | Yes|Parameter by descending of which chats are sorted in the chat list. If order of two chats is equal, then they need to be sorted by id also in descending order. If order == 0, position of the chat in the list is undetermined.|
|unread\_count|[int](../types/int.md) | Yes|Count of unread messages in the chat|
|last\_read\_inbox\_message\_id|[long](../types/long.md) | Yes|Identifier of last read incoming message|
|last\_read\_outbox\_message\_id|[long](../types/long.md) | Yes|Identifier of last read outgoing message|
|notification\_settings|[notificationSettings](../types/notificationSettings.md) | Yes|Notification settings for this chat|
|reply\_markup\_message\_id|[long](../types/long.md) | Yes|Identifier of the message from which reply markup need to be used or 0 if there is no default custom reply markup in the chat|
|draft\_message|[draftMessage](../types/draftMessage.md) | Yes|Draft of a message in the chat, nullable. parse_mode in input_message_text always will be null|
|type|[ChatInfo](../types/ChatInfo.md) | Yes|Information about type of the chat|



### Type: [Chat](../types/Chat.md)


### Example:

```
$chat = ['_' => 'chat', 'id' => long, 'title' => 'string', 'photo' => chatPhoto, 'top_message' => message, 'order' => long, 'unread_count' => int, 'last_read_inbox_message_id' => long, 'last_read_outbox_message_id' => long, 'notification_settings' => notificationSettings, 'reply_markup_message_id' => long, 'draft_message' => draftMessage, 'type' => ChatInfo];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chat", "id": long, "title": "string", "photo": chatPhoto, "top_message": message, "order": long, "unread_count": int, "last_read_inbox_message_id": long, "last_read_outbox_message_id": long, "notification_settings": notificationSettings, "reply_markup_message_id": long, "draft_message": draftMessage, "type": ChatInfo}
```


Or, if you're into Lua:  


```
chat={_='chat', id=long, title='string', photo=chatPhoto, top_message=message, order=long, unread_count=int, last_read_inbox_message_id=long, last_read_outbox_message_id=long, notification_settings=notificationSettings, reply_markup_message_id=long, draft_message=draftMessage, type=ChatInfo}

```


