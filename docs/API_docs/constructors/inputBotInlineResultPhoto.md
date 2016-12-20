---
title: inputBotInlineResultPhoto
description: inputBotInlineResultPhoto attributes, type and example
---
## Constructor: inputBotInlineResultPhoto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[string](../types/string.md) | Required|
|type|[string](../types/string.md) | Required|
|photo|[InputPhoto](../types/InputPhoto.md) | Required|
|send\_message|[InputBotInlineMessage](../types/InputBotInlineMessage.md) | Required|



### Type: [InputBotInlineResult](../types/InputBotInlineResult.md)


### Example:

```
$inputBotInlineResultPhoto = ['_' => inputBotInlineResultPhoto', 'id' => string, 'type' => string, 'photo' => InputPhoto, 'send_message' => InputBotInlineMessage, ];
```