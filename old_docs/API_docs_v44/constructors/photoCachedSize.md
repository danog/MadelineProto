---
title: photoCachedSize
description: photoCachedSize attributes, type and example
---
## Constructor: photoCachedSize  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|type|[string](../types/string.md) | Yes|
|location|[FileLocation](../types/FileLocation.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|
|bytes|[bytes](../types/bytes.md) | Yes|



### Type: [PhotoSize](../types/PhotoSize.md)


### Example:

```
$photoCachedSize = ['_' => 'photoCachedSize', 'type' => 'string', 'location' => FileLocation, 'w' => int, 'h' => int, 'bytes' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "photoCachedSize", "type": "string", "location": FileLocation, "w": int, "h": int, "bytes": "bytes"}
```


Or, if you're into Lua:  


```
photoCachedSize={_='photoCachedSize', type='string', location=FileLocation, w=int, h=int, bytes='bytes'}

```


