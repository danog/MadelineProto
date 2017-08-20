---
title: updateBotPrecheckoutQuery
description: updateBotPrecheckoutQuery attributes, type and example
---
## Constructor: updateBotPrecheckoutQuery  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|query\_id|[long](../types/long.md) | Yes|
|user\_id|[int](../types/int.md) | Yes|
|payload|[bytes](../types/bytes.md) | Yes|
|info|[PaymentRequestedInfo](../types/PaymentRequestedInfo.md) | Optional|
|shipping\_option\_id|[string](../types/string.md) | Optional|
|currency|[string](../types/string.md) | Yes|
|total\_amount|[long](../types/long.md) | Yes|



### Type: [Update](../types/Update.md)


### Example:

```
$updateBotPrecheckoutQuery = ['_' => 'updateBotPrecheckoutQuery', 'query_id' => long, 'user_id' => int, 'payload' => 'bytes', 'info' => PaymentRequestedInfo, 'shipping_option_id' => 'string', 'currency' => 'string', 'total_amount' => long];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "updateBotPrecheckoutQuery", "query_id": long, "user_id": int, "payload": "bytes", "info": PaymentRequestedInfo, "shipping_option_id": "string", "currency": "string", "total_amount": long}
```


Or, if you're into Lua:  


```
updateBotPrecheckoutQuery={_='updateBotPrecheckoutQuery', query_id=long, user_id=int, payload='bytes', info=PaymentRequestedInfo, shipping_option_id='string', currency='string', total_amount=long}

```


