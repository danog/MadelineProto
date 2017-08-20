---
title: sendMessageUploadAudioAction
description: sendMessageUploadAudioAction attributes, type and example
---
## Constructor: sendMessageUploadAudioAction  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|progress|[int](../types/int.md) | Yes|



### Type: [SendMessageAction](../types/SendMessageAction.md)


### Example:

```
$sendMessageUploadAudioAction = ['_' => 'sendMessageUploadAudioAction', 'progress' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "sendMessageUploadAudioAction", "progress": int}
```


Or, if you're into Lua:  


```
sendMessageUploadAudioAction={_='sendMessageUploadAudioAction', progress=int}

```


