---
title: inlineQueryResultVenue
description: Represents information about a venue
---
## Constructor: inlineQueryResultVenue  
[Back to constructors index](index.md)



Represents information about a venue

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|venue|[venue](../types/venue.md) | Yes|The result|
|thumb\_url|[string](../types/string.md) | Yes|Url of the result thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Thumb width, if known|
|thumb\_height|[int](../types/int.md) | Yes|Thumb height, if known|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultVenue = ['_' => 'inlineQueryResultVenue', 'id' => 'string', 'venue' => venue, 'thumb_url' => 'string', 'thumb_width' => int, 'thumb_height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultVenue", "id": "string", "venue": venue, "thumb_url": "string", "thumb_width": int, "thumb_height": int}
```


Or, if you're into Lua:  


```
inlineQueryResultVenue={_='inlineQueryResultVenue', id='string', venue=venue, thumb_url='string', thumb_width=int, thumb_height=int}

```


