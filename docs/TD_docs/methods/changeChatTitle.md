---
title: changeChatTitle
description: Changes chat title. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Title will not change before request to the server completes
---
## Method: changeChatTitle  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes chat title. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Title will not change before request to the server completes

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|title|[string](../types/string.md) | Yes|New title of the chat, 1-255 characters|


### Return type: [Ok](../types/Ok.md)

