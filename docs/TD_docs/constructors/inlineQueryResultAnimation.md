---
title: inlineQueryResultAnimation
description: Represents an animation cached on the telegram server
---
## Constructor: inlineQueryResultAnimation  
[Back to constructors index](index.md)



Represents an animation cached on the telegram server

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|animation|[animation](../types/animation.md) | Yes|The animation|
|title|[string](../types/string.md) | Yes|Animation title|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultAnimation = ['_' => 'inlineQueryResultAnimation', 'id' => 'string', 'animation' => animation, 'title' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultAnimation", "id": "string", "animation": animation, "title": "string"}
```


Or, if you're into Lua:  


```
inlineQueryResultAnimation={_='inlineQueryResultAnimation', id='string', animation=animation, title='string'}

```


