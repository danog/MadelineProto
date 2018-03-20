---
title: addChatMember
description: Adds new member to chat. Members can't be added to private or secret chats. Member will not be added until chat state will be synchronized with the server
---
## Method: addChatMember  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Adds new member to chat. Members can't be added to private or secret chats. Member will not be added until chat state will be synchronized with the server

### Parameters:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[int53](../types/int53.md) | Yes|Chat identifier|
|user\_id|[int](../types/int.md) | Yes|Identifier of the user to add|
|forward\_limit|[int](../types/int.md) | Yes|Number of previous messages from chat to forward to new member, ignored for channel chats. Can't be greater than 300|


### Return type: [Ok](../types/Ok.md)

