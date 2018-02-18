---
title: updateInlineBotCallbackQuery
description: updateInlineBotCallbackQuery attributes, type and example
---
## Constructor: updateInlineBotCallbackQuery  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|query\_id|[long](../types/long.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|msg\_id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Yes|
|chat\_instance|[long](../types/long.md) | Yes|
|data|[bytes](../types/bytes.md) | Optional|
|game\_short\_name|[string](../types/string.md) | Optional|



### Type: [Update](../types/Update.md)


### Example:

```
$updateInlineBotCallbackQuery = ['_' => 'updateInlineBotCallbackQuery', 'query_id' => long, 'user_id' => int, 'msg_id' => InputBotInlineMessageID, 'chat_instance' => long, 'data' => 'bytes', 'game_short_name' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateInlineBotCallbackQuery", "query_id": long, "user_id": int, "msg_id": InputBotInlineMessageID, "chat_instance": long, "data": "bytes", "game_short_name": "string"}
```


Or, if you're into Lua:  


```
updateInlineBotCallbackQuery={_='updateInlineBotCallbackQuery', query_id=long, user_id=int, msg_id=InputBotInlineMessageID, chat_instance=long, data='bytes', game_short_name='string'}

```


