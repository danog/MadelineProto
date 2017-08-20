---
title: sendMessageUploadPhotoAction
description: User uploads a photo
---
## Constructor: sendMessageUploadPhotoAction  
[Back to constructors index](index.md)



User uploads a photo

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|progress|[int](../types/int.md) | Yes|Upload progress in percents|



### Type: [SendMessageAction](../types/SendMessageAction.md)


### Example:

```
$sendMessageUploadPhotoAction = ['_' => 'sendMessageUploadPhotoAction', 'progress' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "sendMessageUploadPhotoAction", "progress": int}
```


Or, if you're into Lua:  


```
sendMessageUploadPhotoAction={_='sendMessageUploadPhotoAction', progress=int}

```


