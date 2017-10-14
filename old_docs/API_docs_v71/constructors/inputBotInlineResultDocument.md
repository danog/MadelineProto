---
title: inputBotInlineResultDocument
description: inputBotInlineResultDocument attributes, type and example
---
## Constructor: inputBotInlineResultDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|type|[string](../types/string.md) | Yes|
|title|[string](../types/string.md) | Optional|
|description|[string](../types/string.md) | Optional|
|document|[InputDocument](../types/InputDocument.md) | Yes|
|send\_message|[InputBotInlineMessage](../types/InputBotInlineMessage.md) | Yes|



### Type: [InputBotInlineResult](../types/InputBotInlineResult.md)


### Example:

```
$inputBotInlineResultDocument = ['_' => 'inputBotInlineResultDocument', 'id' => 'string', 'type' => 'string', 'title' => 'string', 'description' => 'string', 'document' => InputDocument, 'send_message' => InputBotInlineMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineResultDocument", "id": "string", "type": "string", "title": "string", "description": "string", "document": InputDocument, "send_message": InputBotInlineMessage}
```


Or, if you're into Lua:  


```
inputBotInlineResultDocument={_='inputBotInlineResultDocument', id='string', type='string', title='string', description='string', document=InputDocument, send_message=InputBotInlineMessage}

```


