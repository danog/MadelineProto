---
title: sendMessageUploadDocumentAction
description: sendMessageUploadDocumentAction attributes, type and example
---
## Constructor: sendMessageUploadDocumentAction  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|progress|[int](../types/int.md) | Yes|



### Type: [SendMessageAction](../types/SendMessageAction.md)


### Example:

```
$sendMessageUploadDocumentAction = ['_' => 'sendMessageUploadDocumentAction', 'progress' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "sendMessageUploadDocumentAction", "progress": int}
```


Or, if you're into Lua:  


```
sendMessageUploadDocumentAction={_='sendMessageUploadDocumentAction', progress=int}

```


