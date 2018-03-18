---
title: inputMediaPhoto
description: inputMediaPhoto attributes, type and example
---
## Constructor: inputMediaPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[MessageMedia, Message, Update or InputPhoto](../types/InputPhoto.md) | Optional|
|caption|[string](../types/string.md) | Yes|



### Type: [InputMedia](../types/InputMedia.md)


### Example:

```
$inputMediaPhoto = ['_' => 'inputMediaPhoto', 'id' => InputPhoto, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputMediaPhoto", "id": InputPhoto, "caption": "string"}
```


Or, if you're into Lua:  


```
inputMediaPhoto={_='inputMediaPhoto', id=InputPhoto, caption='string'}

```


