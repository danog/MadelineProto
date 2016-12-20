---
title: botInlineMessageMediaGeo
description: botInlineMessageMediaGeo attributes, type and example
---
## Constructor: botInlineMessageMediaGeo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|geo|[GeoPoint](../types/GeoPoint.md) | Required|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [BotInlineMessage](../types/BotInlineMessage.md)


### Example:

```
$botInlineMessageMediaGeo = ['_' => botInlineMessageMediaGeo', 'geo' => GeoPoint, 'reply_markup' => ReplyMarkup, ];
```