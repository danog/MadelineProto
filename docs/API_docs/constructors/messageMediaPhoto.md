---
title: messageMediaPhoto
description: messageMediaPhoto attributes, type and example
---
## Constructor: messageMediaPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|photo|[Photo](../types/Photo.md) | Optional|
|ttl\_seconds|[int](../types/int.md) | Optional|



### Type: [MessageMedia](../types/MessageMedia.md)


### Example:

```
$messageMediaPhoto = ['_' => 'messageMediaPhoto', 'photo' => Photo, 'ttl_seconds' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageMediaPhoto", "photo": Photo, "ttl_seconds": int}
```


Or, if you're into Lua:  


```
messageMediaPhoto={_='messageMediaPhoto', photo=Photo, ttl_seconds=int}

```


