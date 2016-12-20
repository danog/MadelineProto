---
title: updateInlineBotCallbackQuery
---
## Constructor: updateInlineBotCallbackQuery  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|query\_id|[long](../types/long.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|msg\_id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Required|
|chat\_instance|[long](../types/long.md) | Required|
|data|[bytes](../types/bytes.md) | Optional|
|game\_short\_name|[string](../types/string.md) | Optional|



### Type: [Update](../types/Update.md)


### Example:

```
$updateInlineBotCallbackQuery = ['_' => updateInlineBotCallbackQuery', 'query_id' => long, 'user_id' => int, 'msg_id' => InputBotInlineMessageID, 'chat_instance' => long, 'data' => bytes, 'game_short_name' => string, ];
```