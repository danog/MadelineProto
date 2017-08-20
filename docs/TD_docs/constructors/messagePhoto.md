---
title: messagePhoto
description: Photo message
---
## Constructor: messagePhoto  
[Back to constructors index](index.md)



Photo message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|photo|[photo](../types/photo.md) | Yes|Message content|
|caption|[string](../types/string.md) | Yes|Photo caption|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messagePhoto = ['_' => 'messagePhoto', 'photo' => photo, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messagePhoto", "photo": photo, "caption": "string"}
```


Or, if you're into Lua:  


```
messagePhoto={_='messagePhoto', photo=photo, caption='string'}

```


