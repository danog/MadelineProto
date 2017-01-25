---
title: inputBotInlineResultDocument
description: inputBotInlineResultDocument attributes, type and example
---
## Constructor: inputBotInlineResultDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|id|[string](../types/string.md) | Required|
|type|[string](../types/string.md) | Required|
|title|[string](../types/string.md) | Optional|
|description|[string](../types/string.md) | Optional|
|document|[InputDocument](../types/InputDocument.md) | Required|
|send\_message|[InputBotInlineMessage](../types/InputBotInlineMessage.md) | Required|



### Type: [InputBotInlineResult](../types/InputBotInlineResult.md)


### Example:

```
$inputBotInlineResultDocument = ['_' => 'inputBotInlineResultDocument', 'id' => string, 'type' => string, 'title' => string, 'description' => string, 'document' => InputDocument, 'send_message' => InputBotInlineMessage, ];
```  

