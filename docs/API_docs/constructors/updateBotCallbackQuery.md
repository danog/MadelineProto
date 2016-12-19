## Constructor: updateBotCallbackQuery  

### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|query\_id|[long](../types/long.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|peer|[Peer](../types/Peer.md) | Required|
|msg\_id|[int](../types/int.md) | Required|
|chat\_instance|[long](../types/long.md) | Required|
|data|[bytes](../types/bytes.md) | Optional|
|game\_short\_name|[string](../types/string.md) | Optional|


### Type: [Update](../types/Update.md)

### Example:


```
$updateBotCallbackQuery = ['_' => updateBotCallbackQuery', 'query_id' => long, 'user_id' => int, 'peer' => Peer, 'msg_id' => int, 'chat_instance' => long, 'data' => bytes, 'game_short_name' => string, ];
```