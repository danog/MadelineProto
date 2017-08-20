---
title: file
description: Represents a file
---
## Constructor: file  
[Back to constructors index](index.md)



Represents a file

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[int](../types/int.md) | Yes|Unique file identifier, 0 for empty file|
|persistent\_id|[string](../types/string.md) | Yes|Persistent file identifier, if exists. Can be used across application restarts or even other devices for current logged user. If begins with "http: " or "https: ", it is HTTP URL of the file. Currently, TDLib is unable to download files if only they URL is known|
|size|[int](../types/int.md) | Yes|File size, 0 if unknown|
|path|[string](../types/string.md) | Yes|Local path to the file, if available|



### Type: [File](../types/File.md)


### Example:

```
$file = ['_' => 'file', 'id' => int, 'persistent_id' => 'string', 'size' => int, 'path' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "file", "id": int, "persistent_id": "string", "size": int, "path": "string"}
```


Or, if you're into Lua:  


```
file={_='file', id=int, persistent_id='string', size=int, path='string'}

```


