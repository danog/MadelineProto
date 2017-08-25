---
title: changeChatTitle
description: Changes chat title. Title can't be changed for private chats. Title will not change until change will be synchronized with the server. Title will not be changed if application is killed before it can send request to the server. - There will be update about change of the title on success. Otherwise error will be returned
---
## Method: changeChatTitle  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes chat title. Title can't be changed for private chats. Title will not change until change will be synchronized with the server. Title will not be changed if application is killed before it can send request to the server. - There will be update about change of the title on success. Otherwise error will be returned

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|title|[string](../types/string.md) | Yes|New title of a chat, 0-255 characters|


### Return type: [Ok](../types/Ok.md)

