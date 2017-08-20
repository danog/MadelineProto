---
title: inputChatUploadedPhoto
description: inputChatUploadedPhoto attributes, type and example
---
## Constructor: inputChatUploadedPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|file|[InputFile](../types/InputFile.md) | Yes|



### Type: [InputChatPhoto](../types/InputChatPhoto.md)


### Example:

```
$inputChatUploadedPhoto = ['_' => 'inputChatUploadedPhoto', 'file' => InputFile];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputChatUploadedPhoto", "file": InputFile}
```


Or, if you're into Lua:  


```
inputChatUploadedPhoto={_='inputChatUploadedPhoto', file=InputFile}

```


