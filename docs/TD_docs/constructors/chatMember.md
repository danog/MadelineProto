---
title: chatMember
description: User with information about its chat joining/kicking
---
## Constructor: chatMember  
[Back to constructors index](index.md)



User with information about its chat joining/kicking

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|user\_id|[int](../types/int.md) | Yes|User identifier of the chat member|
|inviter\_user\_id|[int](../types/int.md) | Yes|Identifier of a user invited this member to/kicked this member from the chat, 0 if unknown|
|join\_date|[int](../types/int.md) | Yes|Date the user has joined a chat, unix time|
|status|[ChatMemberStatus](../types/ChatMemberStatus.md) | Yes|Status of the member in the chat|
|bot\_info|[botInfo](../types/botInfo.md) | Yes|Information about bot if user is a bot, nullable. Can be null even for bot if bot is not a chat member|



### Type: [ChatMember](../types/ChatMember.md)


### Example:

```
$chatMember = ['_' => 'chatMember', 'user_id' => int, 'inviter_user_id' => int, 'join_date' => int, 'status' => ChatMemberStatus, 'bot_info' => botInfo];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatMember", "user_id": int, "inviter_user_id": int, "join_date": int, "status": ChatMemberStatus, "bot_info": botInfo}
```


Or, if you're into Lua:  


```
chatMember={_='chatMember', user_id=int, inviter_user_id=int, join_date=int, status=ChatMemberStatus, bot_info=botInfo}

```


