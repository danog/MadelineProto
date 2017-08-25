---
title: changeChatMemberStatus
description: Changes status of the chat member, need appropriate privileges. This function is currently not suitable for adding new members to the chat, use addChatMember instead. Status will not be changed until chat state will be synchronized with the server. Status will not be changed if application is killed before it can send request to the server
---
## Method: changeChatMemberStatus  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes status of the chat member, need appropriate privileges. This function is currently not suitable for adding new members to the chat, use addChatMember instead. Status will not be changed until chat state will be synchronized with the server. Status will not be changed if application is killed before it can send request to the server

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|user\_id|[int](../types/int.md) | Yes|Identifier of the user to edit status|
|status|[ChatMemberStatus](../types/ChatMemberStatus.md) | Yes|New status of the member in the chat|


### Return type: [Ok](../types/Ok.md)

