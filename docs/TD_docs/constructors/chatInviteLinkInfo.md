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
|chat\_id|[long](../types/long.md) | Yes|Chat identifier of the invite link or 0 if user is not a member of this chat|
|title|[string](../types/string.md) | Yes|Title of the chat|
|photo|[chatPhoto](../types/chatPhoto.md) | Yes|Chat photo, nullable|
|member\_count|[int](../types/int.md) | Yes|Total member count|
|members|Array of [user](../constructors/user.md) | Yes|Some chat members that may be known to the current user|
|is\_group|[Bool](../types/Bool.md) | Yes|True, if the chat is a group chat|
|is\_channel|[Bool](../types/Bool.md) | Yes|True, if the chat is a channel chat|
|is\_public\_channel|[Bool](../types/Bool.md) | Yes|True, if the chat is a channel chat with set up username|
|is\_supergroup\_channel|[Bool](../types/Bool.md) | Yes|True, if the chat is a supergroup channel chat|



### Type: [ChatInviteLinkInfo](../types/ChatInviteLinkInfo.md)


### Example:

```
$chatInviteLinkInfo = ['_' => 'chatInviteLinkInfo', 'chat_id' => long, 'title' => 'string', 'photo' => chatPhoto, 'member_count' => int, 'members' => [user], 'is_group' => Bool, 'is_channel' => Bool, 'is_public_channel' => Bool, 'is_supergroup_channel' => Bool];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatInviteLinkInfo", "chat_id": long, "title": "string", "photo": chatPhoto, "member_count": int, "members": [user], "is_group": Bool, "is_channel": Bool, "is_public_channel": Bool, "is_supergroup_channel": Bool}
```


Or, if you're into Lua:  


```
chatInviteLinkInfo={_='chatInviteLinkInfo', chat_id=long, title='string', photo=chatPhoto, member_count=int, members={user}, is_group=Bool, is_channel=Bool, is_public_channel=Bool, is_supergroup_channel=Bool}

```


