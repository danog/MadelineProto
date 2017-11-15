---
title: updateChannelMessageViews
description: updateChannelMessageViews attributes, type and example
---
## Constructor: updateChannelMessageViews  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|channel\_id|[int](../types/int.md) | Yes|
|id|[int](../types/int.md) | Yes|
|views|[int](../types/int.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateChannelMessageViews = ['_' => 'updateChannelMessageViews', 'channel_id' => int, 'id' => int, 'views' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateChannelMessageViews", "channel_id": int, "id": int, "views": int}
```


Or, if you're into Lua:  


```
updateChannelMessageViews={_='updateChannelMessageViews', channel_id=int, id=int, views=int}

```


