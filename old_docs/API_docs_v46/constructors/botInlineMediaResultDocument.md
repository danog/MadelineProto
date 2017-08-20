---
title: botInlineMediaResultDocument
description: botInlineMediaResultDocument attributes, type and example
---
## Constructor: botInlineMediaResultDocument  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|type|[string](../types/string.md) | Yes|
|document|[Document](../types/Document.md) | Yes|
|send\_message|[BotInlineMessage](../types/BotInlineMessage.md) | Yes|



### Type: [BotInlineResult](../types/BotInlineResult.md)


### Example:

```
$botInlineMediaResultDocument = ['_' => 'botInlineMediaResultDocument', 'id' => 'string', 'type' => 'string', 'document' => Document, 'send_message' => BotInlineMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInlineMediaResultDocument", "id": "string", "type": "string", "document": Document, "send_message": BotInlineMessage}
```


Or, if you're into Lua:  


```
botInlineMediaResultDocument={_='botInlineMediaResultDocument', id='string', type='string', document=Document, send_message=BotInlineMessage}

```


