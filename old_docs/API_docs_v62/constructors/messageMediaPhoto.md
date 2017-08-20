---
title: messageMediaPhoto
description: messageMediaPhoto attributes, type and example
---
## Constructor: messageMediaPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|photo|[Photo](../types/Photo.md) | Yes|
|caption|[string](../types/string.md) | Yes|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaPhoto = ['_' => 'messageMediaPhoto', 'photo' => Photo, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaPhoto", "photo": Photo, "caption": "string"}
```


Or, if you're into Lua:  


```
messageMediaPhoto={_='messageMediaPhoto', photo=Photo, caption='string'}

```


