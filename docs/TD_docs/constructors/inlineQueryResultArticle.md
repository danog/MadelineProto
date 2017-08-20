---
title: inlineQueryResultArticle
description: Represents link to an article or web page
---
## Constructor: inlineQueryResultArticle  
[Back to constructors index](index.md)



Represents link to an article or web page

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|url|[string](../types/string.md) | Yes|Url of the result, if exists|
|hide\_url|[Bool](../types/Bool.md) | Yes|True, if url must be not shown|
|title|[string](../types/string.md) | Yes|Title of the result|
|description|[string](../types/string.md) | Yes|Short description of the result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the result thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Thumb width, if known|
|thumb\_height|[int](../types/int.md) | Yes|Thumb height, if known|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultArticle = ['_' => 'inlineQueryResultArticle', 'id' => 'string', 'url' => 'string', 'hide_url' => Bool, 'title' => 'string', 'description' => 'string', 'thumb_url' => 'string', 'thumb_width' => int, 'thumb_height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultArticle", "id": "string", "url": "string", "hide_url": Bool, "title": "string", "description": "string", "thumb_url": "string", "thumb_width": int, "thumb_height": int}
```


Or, if you're into Lua:  


```
inlineQueryResultArticle={_='inlineQueryResultArticle', id='string', url='string', hide_url=Bool, title='string', description='string', thumb_url='string', thumb_width=int, thumb_height=int}

```


