---
title: animations
description: Represents list of animations
---
## Constructor: animations  
[Back to constructors index](index.md)



Represents list of animations

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|animations|Array of [animation](../constructors/animation.md) | Yes|Animations|



### Type: [Animations](../types/Animations.md)


### Example:

```
$animations = ['_' => 'animations', 'animations' => [animation]];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "animations", "animations": [animation]}
```


Or, if you're into Lua:  


```
animations={_='animations', animations={animation}}

```


