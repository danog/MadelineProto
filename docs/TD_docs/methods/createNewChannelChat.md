---
title: createNewChannelChat
description: Creates new channel chat and send corresponding messageChannelChatCreate, returns created chat
---
## Method: createNewChannelChat  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Creates new channel chat and send corresponding messageChannelChatCreate, returns created chat

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|title|[string](../types/string.md) | Yes|Title of new channel chat, 1-255 characters|
|is\_supergroup|[Bool](../types/Bool.md) | Yes|True, if supergroup chat should be created|
|description|[string](../types/string.md) | Yes|Channel description, 0-255 characters|


### Return type: [Chat](../types/Chat.md)

