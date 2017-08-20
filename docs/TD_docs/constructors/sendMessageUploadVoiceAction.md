---
title: sendMessageUploadVoiceAction
description: User uploads voice message
---
## Constructor: sendMessageUploadVoiceAction  
[Back to constructors index](index.md)



User uploads voice message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|progress|[int](../types/int.md) | Yes|Upload progress in percents|



### Type: [SendMessageAction](../types/SendMessageAction.md)


### Example:

```
$sendMessageUploadVoiceAction = ['_' => 'sendMessageUploadVoiceAction', 'progress' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "sendMessageUploadVoiceAction", "progress": int}
```


Or, if you're into Lua:  


```
sendMessageUploadVoiceAction={_='sendMessageUploadVoiceAction', progress=int}

```


