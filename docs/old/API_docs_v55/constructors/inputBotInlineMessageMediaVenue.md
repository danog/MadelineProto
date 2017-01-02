---
title: inputBotInlineMessageMediaVenue
description: inputBotInlineMessageMediaVenue attributes, type and example
---
## Constructor: inputBotInlineMessageMediaVenue  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Required|
|title|[string](../types/string.md) | Required|
|address|[string](../types/string.md) | Required|
|provider|[string](../types/string.md) | Required|
|venue\_id|[string](../types/string.md) | Required|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)


### Example:

```
$inputBotInlineMessageMediaVenue = ['_' => 'inputBotInlineMessageMediaVenue', 'geo_point' => InputGeoPoint, 'title' => string, 'address' => string, 'provider' => string, 'venue_id' => string, 'reply_markup' => ReplyMarkup, ];
```  

