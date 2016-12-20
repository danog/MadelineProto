---
title: updateBotInlineSend
---
## Constructor: updateBotInlineSend  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|query|[string](../types/string.md) | Required|
|geo|[GeoPoint](../types/GeoPoint.md) | Optional|
|id|[string](../types/string.md) | Required|
|msg\_id|[InputBotInlineMessageID](../types/InputBotInlineMessageID.md) | Optional|



### Type: [Update](../types/Update.md)


### Example:

```
$updateBotInlineSend = ['_' => updateBotInlineSend', 'user_id' => int, 'query' => string, 'geo' => GeoPoint, 'id' => string, 'msg_id' => InputBotInlineMessageID, ];
```