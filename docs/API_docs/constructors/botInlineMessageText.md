---
title: botInlineMessageText
description: botInlineMessageText attributes, type and example
---
## Constructor: botInlineMessageText  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|message|[string](../types/string.md) | Required|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [BotInlineMessage](../types/BotInlineMessage.md)


### Example:

```
$botInlineMessageText = ['_' => botInlineMessageText, 'no_webpage' => true, 'message' => string, 'entities' => [Vector t], 'reply_markup' => ReplyMarkup, ];
```