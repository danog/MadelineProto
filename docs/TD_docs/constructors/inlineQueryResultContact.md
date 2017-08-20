---
title: inlineQueryResultContact
description: Represents user contact
---
## Constructor: inlineQueryResultContact  
[Back to constructors index](index.md)



Represents user contact

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|id|[string](../types/string.md) | Yes|Unique identifier of this result|
|contact|[contact](../types/contact.md) | Yes|User contact|
|thumb\_url|[string](../types/string.md) | Yes|Url of the result thumb, if exists|
|thumb\_width|[int](../types/int.md) | Yes|Thumb width, if known|
|thumb\_height|[int](../types/int.md) | Yes|Thumb height, if known|



### Type: [InlineQueryResult](../types/InlineQueryResult.md)


### Example:

```
$inlineQueryResultContact = ['_' => 'inlineQueryResultContact', 'id' => 'string', 'contact' => contact, 'thumb_url' => 'string', 'thumb_width' => int, 'thumb_height' => int];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineQueryResultContact", "id": "string", "contact": contact, "thumb_url": "string", "thumb_width": int, "thumb_height": int}
```


Or, if you're into Lua:  


```
inlineQueryResultContact={_='inlineQueryResultContact', id='string', contact=contact, thumb_url='string', thumb_width=int, thumb_height=int}

```


