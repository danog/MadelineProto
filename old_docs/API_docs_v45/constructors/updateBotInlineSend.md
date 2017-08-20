---
title: updateBotInlineSend
description: updateBotInlineSend attributes, type and example
---
## Constructor: updateBotInlineSend  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|user\_id|[int](../types/int.md) | Yes|
|query|[string](../types/string.md) | Yes|
|id|[string](../types/string.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateBotInlineSend = ['_' => 'updateBotInlineSend', 'user_id' => int, 'query' => 'string', 'id' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateBotInlineSend", "user_id": int, "query": "string", "id": "string"}
```


Or, if you're into Lua:  


```
updateBotInlineSend={_='updateBotInlineSend', user_id=int, query='string', id='string'}

```


