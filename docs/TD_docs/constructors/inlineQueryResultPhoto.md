---
title: inlineQueryResultPhoto
description: Represents a photo cached on the telegram server
---
## Constructor: inlineQueryResultPhoto  
[Back to constructors index](index.md)



Represents a photo cached on the telegram server

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|photo|[photo](../types/photo.md) | Yes|The photo|
|title|[string](../types/string.md) | Yes|Title of the result, if known|
|description|[string](../types/string.md) | Yes|Short description of the result, if known|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultPhoto = ['_' => 'inlineQueryResultPhoto', 'id' => 'string', 'photo' => photo, 'title' => 'string', 'description' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultPhoto", "id": "string", "photo": photo, "title": "string", "description": "string"}
```


Or, if you're into Lua:  


```
inlineQueryResultPhoto={_='inlineQueryResultPhoto', id='string', photo=photo, title='string', description='string'}

```


