---
title: chatPhoto
description: Describes chat photo
---
## Constructor: chatPhoto  
[Back to constructors index](index.md)



Describes chat photo

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|small|[file](../types/file.md) | Yes|Small (160x160) chat photo|
|big|[file](../types/file.md) | Yes|Big (640x640) chat photo|



### Type: [ChatPhoto](../types/ChatPhoto.md)


### Example:

```
$chatPhoto = ['_' => 'chatPhoto', 'small' => file, 'big' => file];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "chatPhoto", "small": file, "big": file}
```


Or, if you're into Lua:  


```
chatPhoto={_='chatPhoto', small=file, big=file}

```


