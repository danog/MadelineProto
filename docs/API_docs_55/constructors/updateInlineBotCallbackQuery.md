---
title: updateInlineBotCallbackQuery
description: updateInlineBotCallbackQuery attributes, type and example
---
## Constructor: updateInlineBotCallbackQuery  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|query\_id|[long](../types/long.md) | Required|
|user\_id|[int](../types/int.md) | Required|
|msg\_id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Required|
|data|[bytes](../types/bytes.md) | Required|



### Type: [Update](../types/Update.md)


### Example:

```
$updateInlineBotCallbackQuery = ['_' => 'updateInlineBotCallbackQuery', 'query_id' => long, 'user_id' => int, 'msg_id' => InputBotInlineMessageID, 'data' => bytes, ];
```