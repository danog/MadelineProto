---
title: updateBotCallbackQuery
description: updateBotCallbackQuery attributes, type and example
---
## Constructor: updateBotCallbackQuery  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|query\_id|[long](../types/long.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|peer|[Peer](../types/Peer.md) | Yes|
|msg\_id|[int](../types/int.md) | Yes|
|data|[bytes](../types/bytes.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateBotCallbackQuery = ['_' => 'updateBotCallbackQuery', 'query_id' => long, 'user_id' => int, 'peer' => Peer, 'msg_id' => int, 'data' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateBotCallbackQuery", "query_id": long, "user_id": int, "peer": Peer, "msg_id": int, "data": "bytes"}
```


Or, if you're into Lua:  


```
updateBotCallbackQuery={_='updateBotCallbackQuery', query_id=long, user_id=int, peer=Peer, msg_id=int, data='bytes'}

```


