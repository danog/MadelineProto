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
|data|[bytes](../types/bytes.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateInlineBotCallbackQuery = ['_' => 'updateInlineBotCallbackQuery', 'query_id' => long, 'user_id' => int, 'msg_id' => InputBotInlineMessageID, 'data' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateInlineBotCallbackQuery", "query_id": long, "user_id": int, "msg_id": InputBotInlineMessageID, "data": "bytes"}
```


Or, if you're into Lua:  


```
updateInlineBotCallbackQuery={_='updateInlineBotCallbackQuery', query_id=long, user_id=int, msg_id=InputBotInlineMessageID, data='bytes'}

```


