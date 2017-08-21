---
title: botInlineMessageMediaGeo
description: botInlineMessageMediaGeo attributes, type and example
---
## Constructor: botInlineMessageMediaGeo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo|[GeoPoint](../types/GeoPoint.md) | Yes|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [BotInlineMessage](../types/BotInlineMessage.md)


### Example:

```
$botInlineMessageMediaGeo = ['_' => 'botInlineMessageMediaGeo', 'geo' => GeoPoint, 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInlineMessageMediaGeo", "geo": GeoPoint, "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
botInlineMessageMediaGeo={_='botInlineMessageMediaGeo', geo=GeoPoint, reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


