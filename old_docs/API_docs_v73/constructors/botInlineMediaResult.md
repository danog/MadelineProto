---
title: botInlineMediaResult
description: botInlineMediaResult attributes, type and example
---
## Constructor: botInlineMediaResult  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|type|[string](../types/string.md) | Yes|
|photo|[Photo](../types/Photo.md) | Optional|
|document|[Document](../types/Document.md) | Optional|
|title|[string](../types/string.md) | Optional|
|description|[string](../types/string.md) | Optional|
|send\_message|[BotInlineMessage](../types/BotInlineMessage.md) | Yes|



### Type: [BotInlineResult](../types/BotInlineResult.md)


### Example:

```
$botInlineMediaResult = ['_' => 'botInlineMediaResult', 'id' => 'string', 'type' => 'string', 'photo' => Photo, 'document' => Document, 'title' => 'string', 'description' => 'string', 'send_message' => BotInlineMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInlineMediaResult", "id": "string", "type": "string", "photo": Photo, "document": Document, "title": "string", "description": "string", "send_message": BotInlineMessage}
```


Or, if you're into Lua:  


```
botInlineMediaResult={_='botInlineMediaResult', id='string', type='string', photo=Photo, document=Document, title='string', description='string', send_message=BotInlineMessage}

```


