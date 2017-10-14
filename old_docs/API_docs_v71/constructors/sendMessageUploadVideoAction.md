---
title: sendMessageUploadVideoAction
description: sendMessageUploadVideoAction attributes, type and example
---
## Constructor: sendMessageUploadVideoAction  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|progress|[int](../types/int.md) | Yes|



### Type: [SendMessageAction](../types/SendMessageAction.md)


### Example:

```
$sendMessageUploadVideoAction = ['_' => 'sendMessageUploadVideoAction', 'progress' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "sendMessageUploadVideoAction", "progress": int}
```


Or, if you're into Lua:  


```
sendMessageUploadVideoAction={_='sendMessageUploadVideoAction', progress=int}

```


