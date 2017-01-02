---
title: updateBotCallbackQuery
description: updateBotCallbackQuery attributes, type and example
---
## Constructor: updateBotCallbackQuery  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|query\_id|[long](../types/long.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|peer|[Peer](../types/Peer.md) | Required|
|msg\_id|[int](../types/int.md) | Required|
|data|[bytes](../types/bytes.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateBotCallbackQuery = ['_' => 'updateBotCallbackQuery', 'query_id' => long, 'user_id' => int, 'peer' => Peer, 'msg_id' => int, 'data' => bytes, ];
```  

