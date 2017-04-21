---
title: chatPhoto
description: chatPhoto attributes, type and example
---
## Constructor: chatPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|photo\_small|[FileLocation](../types/FileLocation.md) | Yes|
|photo\_big|[FileLocation](../types/FileLocation.md) | Yes|



### Type: [ChatPhoto](../types/ChatPhoto.md)


### Example:

```
$chatPhoto = ['_' => 'chatPhoto', 'photo_small' => FileLocation, 'photo_big' => FileLocation, ];
```  

Or, if you're into Lua:  


```
chatPhoto={_='chatPhoto', photo_small=FileLocation, photo_big=FileLocation, }

```


