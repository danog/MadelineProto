---
title: inlineQueryResultLocation
description: Represents a point on the map
---
## Constructor: inlineQueryResultLocation  
[Back to constructors index](index.md)



Represents a point on the map

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|location|[location](../types/location.md) | Yes|The result|
|title|[string](../types/string.md) | Yes|Title of the result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the result thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Thumb width, if known|
|thumb\_height|[int](../types/int.md) | Yes|Thumb height, if known|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultLocation = ['_' => 'inlineQueryResultLocation', 'id' => 'string', 'location' => location, 'title' => 'string', 'thumb_url' => 'string', 'thumb_width' => int, 'thumb_height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultLocation", "id": "string", "location": location, "title": "string", "thumb_url": "string", "thumb_width": int, "thumb_height": int}
```


Or, if you're into Lua:  


```
inlineQueryResultLocation={_='inlineQueryResultLocation', id='string', location=location, title='string', thumb_url='string', thumb_width=int, thumb_height=int}

```


