---
title: changeChatPhoto
description: Changes chat photo. Photo can't be changed for private chats. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. - There will be update about change of the photo on success. Otherwise error will be returned
---
## Method: changeChatPhoto  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Changes chat photo. Photo can't be changed for private chats. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. - There will be update about change of the photo on success. Otherwise error will be returned

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier|
|photo|[InputFile](../types/InputFile.md) | Yes|New chat photo. You can use zero InputFileId to delete photo. Files accessible only by HTTP URL are not acceptable|


### Return type: [Ok](../types/Ok.md)

