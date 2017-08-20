---
title: messageChatChangePhoto
description: Chat photo changed
---
## Constructor: messageChatChangePhoto  
[Back to constructors index](index.md)



Chat photo changed

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|photo|[photo](../types/photo.md) | Yes|New chat photo|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageChatChangePhoto = ['_' => 'messageChatChangePhoto', 'photo' => photo];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageChatChangePhoto", "photo": photo}
```


Or, if you're into Lua:  


```
messageChatChangePhoto={_='messageChatChangePhoto', photo=photo}

```


