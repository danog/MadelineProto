---
title: pong
description: pong attributes, type and example
---
## Constructor: pong  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|msg\_id|[long](../types/long.md) | Yes|
|ping\_id|[long](../types/long.md) | Yes|



### Type: [Pong](../types/Pong.md)


### Example:

```
$pong = ['_' => 'pong', 'msg_id' => long, 'ping_id' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "pong", "msg_id": long, "ping_id": long}
```


Or, if you're into Lua:  


```
pong={_='pong', msg_id=long, ping_id=long}

```


