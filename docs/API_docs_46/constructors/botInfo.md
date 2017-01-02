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
|version|[int](../types/int.md) | Required|
|share\_text|[string](../types/string.md) | Required|
|description|[string](../types/string.md) | Required|
|commands|Array of [BotCommand](../types/BotCommand.md) | Required|



### Type: [BotInfo](../types/BotInfo.md)


### Example:

```
$botInfo = ['_' => 'botInfo', 'user_id' => int, 'version' => int, 'share_text' => string, 'description' => string, 'commands' => [Vector t], ];
```