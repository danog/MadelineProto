---
title: sendMessageUploadPhotoAction
description: sendMessageUploadPhotoAction attributes, type and example
---
## Constructor: sendMessageUploadPhotoAction  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|progress|[int](../types/int.md) | Yes|



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


