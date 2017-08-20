---
title: inputChatPhoto
description: inputChatPhoto attributes, type and example
---
## Constructor: inputChatPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[InputPhoto](../types/InputPhoto.md) | Yes|
|crop|[InputPhotoCrop](../types/InputPhotoCrop.md) | Yes|



### Type: [InputChatPhoto](../types/InputChatPhoto.md)


### Example:

```
$inputChatPhoto = ['_' => 'inputChatPhoto', 'id' => InputPhoto, 'crop' => InputPhotoCrop];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputChatPhoto", "id": InputPhoto, "crop": InputPhotoCrop}
```


Or, if you're into Lua:  


```
inputChatPhoto={_='inputChatPhoto', id=InputPhoto, crop=InputPhotoCrop}

```


