---
title: inputBotInlineMessageMediaGeo
description: inputBotInlineMessageMediaGeo attributes, type and example
---
## Constructor: inputBotInlineMessageMediaGeo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|:-------------:|---------:|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Required|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)


### Example:

```
$inputBotInlineMessageMediaGeo = ['_' => inputBotInlineMessageMediaGeo, 'geo_point' => InputGeoPoint, 'reply_markup' => ReplyMarkup, ];
```