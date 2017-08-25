---
title: setProfilePhoto
description: Uploads new profile photo for logged in user. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. If something changes, updateUser will be sent
---
## Method: setProfilePhoto  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Uploads new profile photo for logged in user. Photo will not change until change will be synchronized with the server. Photo will not be changed if application is killed before it can send request to the server. If something changes, updateUser will be sent

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|photo|[InputFile](../types/InputFile.md) | Yes|Profile photo to set. inputFileId and inputFilePersistentId may be unsupported|


### Return type: [Ok](../types/Ok.md)

