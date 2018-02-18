---
title: botInlineResult
description: botInlineResult attributes, type and example
---
## Constructor: botInlineResult  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|id|[string](../types/string.md) | Yes|
|type|[string](../types/string.md) | Yes|
|title|[string](../types/string.md) | Optional|
|description|[string](../types/string.md) | Optional|
|url|[string](../types/string.md) | Optional|
|thumb\_url|[string](../types/string.md) | Optional|
|content\_url|[string](../types/string.md) | Optional|
|content\_type|[string](../types/string.md) | Optional|
|w|[int](../types/int.md) | Optional|
|h|[int](../types/int.md) | Optional|
|duration|[int](../types/int.md) | Optional|
|send\_message|[BotInlineMessage](../types/BotInlineMessage.md) | Yes|



### Type: [BotInlineResult](../types/BotInlineResult.md)


### Example:

```
$botInlineResult = ['_' => 'botInlineResult', 'id' => 'string', 'type' => 'string', 'title' => 'string', 'description' => 'string', 'url' => 'string', 'thumb_url' => 'string', 'content_url' => 'string', 'content_type' => 'string', 'w' => int, 'h' => int, 'duration' => int, 'send_message' => BotInlineMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInlineResult", "id": "string", "type": "string", "title": "string", "description": "string", "url": "string", "thumb_url": "string", "content_url": "string", "content_type": "string", "w": int, "h": int, "duration": int, "send_message": BotInlineMessage}
```


Or, if you're into Lua:  


```
botInlineResult={_='botInlineResult', id='string', type='string', title='string', description='string', url='string', thumb_url='string', content_url='string', content_type='string', w=int, h=int, duration=int, send_message=BotInlineMessage}

```


