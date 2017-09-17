---
title: addChatMembers
description: Adds many new members to the chat. Currently, available only for channels. Can't be used to join the channel. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server
---
## Method: addChatMembers  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Adds many new members to the chat. Currently, available only for channels. Can't be used to join the channel. Member will not be added until chat state will be synchronized with the server. Member will not be added if application is killed before it can send request to the server

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|user\_ids|Array of [int](../types/int.md) | Yes|Identifiers of the users to add|


### Return type: [Ok](../types/Ok.md)

