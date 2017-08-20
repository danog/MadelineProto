---
title: messageAnimation
description: Animation message
---
## Constructor: messageAnimation  
[Back to constructors index](index.md)



Animation message

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|animation|[animation](../types/animation.md) | Yes|Message content|
|caption|[string](../types/string.md) | Yes|Animation caption|



### Type: [MessageContent](../types/MessageContent.md)


### Example:

```
$messageAnimation = ['_' => 'messageAnimation', 'animation' => animation, 'caption' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "messageAnimation", "animation": animation, "caption": "string"}
```


Or, if you're into Lua:  


```
messageAnimation={_='messageAnimation', animation=animation, caption='string'}

```


