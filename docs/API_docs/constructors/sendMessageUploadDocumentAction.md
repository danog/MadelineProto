---
title: sendMessageUploadDocumentAction
description: User uploads a document
---
## Constructor: sendMessageUploadDocumentAction  
[Back to constructors index](index.md)



User uploads a document

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|progress|[int](../types/int.md) | Yes|Upload progress in percents|



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


