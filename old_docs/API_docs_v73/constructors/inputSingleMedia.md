---
title: inputSingleMedia
description: inputSingleMedia attributes, type and example
---
## Constructor: inputSingleMedia  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|media|[MessageMedia, Message, Update or InputMedia](../types/InputMedia.md) | Optional|



### Type: [InputSingleMedia](../types/InputSingleMedia.md)


### Example:

```
$inputSingleMedia = ['_' => 'inputSingleMedia', 'media' => InputMedia];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputSingleMedia", "media": InputMedia}
```


Or, if you're into Lua:  


```
inputSingleMedia={_='inputSingleMedia', media=InputMedia}

```


