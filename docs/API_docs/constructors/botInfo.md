---
title: botInfo
description: botInfo attributes, type and example
---
## Constructor: botInfo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|user\_id|[int](../types/int.md) | Required|
|description|[string](../types/string.md) | Required|
|commands|Array of [BotCommand](../types/BotCommand.md) | Required|



### Type: [BotInfo](../types/BotInfo.md)


### Example:

```
$botInfo = ['_' => botInfo', 'user_id' => int, 'description' => string, 'commands' => [Vector t], ];
```