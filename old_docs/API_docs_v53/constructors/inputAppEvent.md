---
title: inputAppEvent
description: inputAppEvent attributes, type and example
---
## Constructor: inputAppEvent  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|time|[double](../types/double.md) | Yes|
|type|[string](../types/string.md) | Yes|
|peer|[long](../types/long.md) | Yes|
|data|[string](../types/string.md) | Yes|



### Type: [InputAppEvent](../types/InputAppEvent.md)


### Example:

```
$inputAppEvent = ['_' => 'inputAppEvent', 'time' => double, 'type' => 'string', 'peer' => long, 'data' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputAppEvent", "time": double, "type": "string", "peer": long, "data": "string"}
```


Or, if you're into Lua:  


```
inputAppEvent={_='inputAppEvent', time=double, type='string', peer=long, data='string'}

```


