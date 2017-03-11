---
title: sendMessageUploadVideoAction
description: User uploads a video
---
## Constructor: sendMessageUploadVideoAction  
[Back to constructors index](index.md)



User uploads a video

### Attributes:

| Name     |    Type       | Required | Description |
|----------|:-------------:|:--------:|------------:|
|progress|[int](../types/int.md) | Yes|Upload progress in percents|



### Type: [SendMessageAction](../types/SendMessageAction.md)


### Example:

```
$sendMessageUploadVideoAction = ['_' => 'sendMessageUploadVideoAction', 'progress' => int, ];
```  

Or, if you're into Lua:  


```
sendMessageUploadVideoAction={_='sendMessageUploadVideoAction', progress=int, }

```


