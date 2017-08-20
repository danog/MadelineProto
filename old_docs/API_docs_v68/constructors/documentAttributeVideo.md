---
title: documentAttributeVideo
description: documentAttributeVideo attributes, type and example
---
## Constructor: documentAttributeVideo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|round\_message|[Bool](../types/Bool.md) | Optional|
|duration|[int](../types/int.md) | Yes|
|w|[int](../types/int.md) | Yes|
|h|[int](../types/int.md) | Yes|



### Type: [DocumentAttribute](../types/DocumentAttribute.md)


### Example:

```
$documentAttributeVideo = ['_' => 'documentAttributeVideo', 'round_message' => Bool, 'duration' => int, 'w' => int, 'h' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "documentAttributeVideo", "round_message": Bool, "duration": int, "w": int, "h": int}
```


Or, if you're into Lua:  


```
documentAttributeVideo={_='documentAttributeVideo', round_message=Bool, duration=int, w=int, h=int}

```


