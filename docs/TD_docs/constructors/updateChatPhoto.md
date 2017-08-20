---
title: updateChatPhoto
description: Chat photo was changed
---
## Constructor: updateChatPhoto  
[Back to constructors index](index.md)



Chat photo was changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|chat\_id|[long](../types/long.md) | Yes|Chat identifier|
|photo|[chatPhoto](../types/chatPhoto.md) | Yes|New chat photo, nullable|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChatPhoto = ['_' => 'updateChatPhoto', 'chat_id' => long, 'photo' => chatPhoto];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChatPhoto", "chat_id": long, "photo": chatPhoto}
```


Or, if you're into Lua:  


```
updateChatPhoto={_='updateChatPhoto', chat_id=long, photo=chatPhoto}

```


