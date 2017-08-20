---
title: inputBotInlineResult
description: inputBotInlineResult attributes, type and example
---
## Constructor: inputBotInlineResult  
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
|send\_message|[InputBotInlineMessage](../types/InputBotInlineMessage.md) | Yes|



### Type: [InputBotInlineResult](../types/InputBotInlineResult.md)


### Example:

```
$inputBotInlineResult = ['_' => 'inputBotInlineResult', 'id' => 'string', 'type' => 'string', 'title' => 'string', 'description' => 'string', 'url' => 'string', 'thumb_url' => 'string', 'content_url' => 'string', 'content_type' => 'string', 'w' => int, 'h' => int, 'duration' => int, 'send_message' => InputBotInlineMessage];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineResult", "id": "string", "type": "string", "title": "string", "description": "string", "url": "string", "thumb_url": "string", "content_url": "string", "content_type": "string", "w": int, "h": int, "duration": int, "send_message": InputBotInlineMessage}
```


Or, if you're into Lua:  


```
inputBotInlineResult={_='inputBotInlineResult', id='string', type='string', title='string', description='string', url='string', thumb_url='string', content_url='string', content_type='string', w=int, h=int, duration=int, send_message=InputBotInlineMessage}

```


