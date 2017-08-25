---
title: openMessageContent
description: Message content is opened, for example the user has opened a photo, a video, a document, a location or a venue or have listened to an audio or a voice message. You will receive updateOpenMessageContent if something has changed
---
## Method: openMessageContent  
[Back to methods index](index.md)


YOU CANNOT USE THIS METHOD IN MADELINEPROTO


Message content is opened, for example the user has opened a photo, a video, a document, a location or a venue or have listened to an audio or a voice message. You will receive updateOpenMessageContent if something has changed

### Params:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[InputPeer](../types/InputPeer.md) | Yes|Chat identifier of the message|
|message\_id|[int53](../types/int53.md) | Yes|Identifier of the message with opened content|


### Return type: [Ok](../types/Ok.md)

