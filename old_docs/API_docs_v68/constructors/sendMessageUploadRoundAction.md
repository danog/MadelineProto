---
title: sendMessageUploadRoundAction
description: sendMessageUploadRoundAction attributes, type and example
---
## Constructor: sendMessageUploadRoundAction  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|progress|[int](../types/int.md) | Yes|



### Type: [SendMessageAction](../types/SendMessageAction.md)


### Example:

```
$sendMessageUploadRoundAction = ['_' => 'sendMessageUploadRoundAction', 'progress' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "sendMessageUploadRoundAction", "progress": int}
```


Or, if you're into Lua:  


```
sendMessageUploadRoundAction={_='sendMessageUploadRoundAction', progress=int}

```


