---
title: botInlineMessageMediaVenue
description: botInlineMessageMediaVenue attributes, type and example
---
## Constructor: botInlineMessageMediaVenue  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|geo|[GeoPoint](../types/GeoPoint.md) | Required|
|title|[string](../types/string.md) | Required|
|address|[string](../types/string.md) | Required|
|provider|[string](../types/string.md) | Required|
|venue\_id|[string](../types/string.md) | Required|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [BotInlineMessage](../types/BotInlineMessage.md)


### Example:

```
$botInlineMessageMediaVenue = ['_' => 'botInlineMessageMediaVenue', 'geo' => GeoPoint, 'title' => string, 'address' => string, 'provider' => string, 'venue_id' => string, 'reply_markup' => ReplyMarkup, ];
```  

