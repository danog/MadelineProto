---
title: botInlineMessageMediaAuto
description: botInlineMessageMediaAuto attributes, type and example
---
## Constructor: botInlineMessageMediaAuto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[string](../types/string.md) | Yes|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [BotInlineMessage](../types/BotInlineMessage.md)


### Example:

```
$botInlineMessageMediaAuto = ['_' => 'botInlineMessageMediaAuto', 'message' => 'string', 'entities' => [MessageEntity, MessageEntity], 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInlineMessageMediaAuto", "message": "string", "entities": [MessageEntity], "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
botInlineMessageMediaAuto={_='botInlineMessageMediaAuto', message='string', entities={MessageEntity}, reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


