---
title: botInlineMediaResultPhoto
description: botInlineMediaResultPhoto attributes, type and example
---
## Constructor: botInlineMediaResultPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[string](../types/string.md) | Required|
|type|[string](../types/string.md) | Required|
|photo|[Photo](../types/Photo.md) | Required|
|send\_message|[BotInlineMessage](../types/BotInlineMessage.md) | Required|



### Type: [BotInlineResult](../types/BotInlineResult.md)


### Example:

```
$botInlineMediaResultPhoto = ['_' => 'botInlineMediaResultPhoto', 'id' => string, 'type' => string, 'photo' => Photo, 'send_message' => BotInlineMessage, ];
```