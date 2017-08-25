---
title: changeChatPhoto
description: Changes chat photo. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Photo will not change before request to the server completes
---
## Method: changeChatPhoto  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes chat photo. Works only for group and channel chats. Requires administrator rights in groups and appropriate administrator right in channels. Photo will not change before request to the server completes

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|photo|[InputFile](../types/InputFile.md) | Yes|New chat photo. You can use zero InputFileId to delete chat photo. Files accessible only by HTTP URL are not acceptable|


### Return type: [Ok](../types/Ok.md)

