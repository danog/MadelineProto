---
title: messageActionChatEditPhoto
description: messageActionChatEditPhoto attributes, type and example
---
## Constructor: messageActionChatEditPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|photo|[Photo](../types/Photo.md) | Yes|



### Type: [MessageAction](../types/MessageAction.md)


### Example:

```
$messageActionChatEditPhoto = ['_' => 'messageActionChatEditPhoto', 'photo' => Photo];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageActionChatEditPhoto", "photo": Photo}
```


Or, if you're into Lua:  


```
messageActionChatEditPhoto={_='messageActionChatEditPhoto', photo=Photo}

```


