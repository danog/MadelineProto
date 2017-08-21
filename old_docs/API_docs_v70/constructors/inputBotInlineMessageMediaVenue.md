---
title: inputBotInlineMessageMediaVenue
description: inputBotInlineMessageMediaVenue attributes, type and example
---
## Constructor: inputBotInlineMessageMediaVenue  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|
|title|[string](../types/string.md) | Yes|
|address|[string](../types/string.md) | Yes|
|provider|[string](../types/string.md) | Yes|
|venue\_id|[string](../types/string.md) | Yes|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)


### Example:

```
$inputBotInlineMessageMediaVenue = ['_' => 'inputBotInlineMessageMediaVenue', 'geo_point' => InputGeoPoint, 'title' => 'string', 'address' => 'string', 'provider' => 'string', 'venue_id' => 'string', 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineMessageMediaVenue", "geo_point": InputGeoPoint, "title": "string", "address": "string", "provider": "string", "venue_id": "string", "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
inputBotInlineMessageMediaVenue={_='inputBotInlineMessageMediaVenue', geo_point=InputGeoPoint, title='string', address='string', provider='string', venue_id='string', reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


