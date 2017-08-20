---
title: inputBotInlineMessageMediaGeo
description: inputBotInlineMessageMediaGeo attributes, type and example
---
## Constructor: inputBotInlineMessageMediaGeo  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|geo\_point|[InputGeoPoint](../types/InputGeoPoint.md) | Yes|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)


### Example:

```
$inputBotInlineMessageMediaGeo = ['_' => 'inputBotInlineMessageMediaGeo', 'geo_point' => InputGeoPoint, 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineMessageMediaGeo", "geo_point": InputGeoPoint, "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
inputBotInlineMessageMediaGeo={_='inputBotInlineMessageMediaGeo', geo_point=InputGeoPoint, reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


