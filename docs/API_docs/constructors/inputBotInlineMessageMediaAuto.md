---
title: inputBotInlineMessageMediaAuto
description: inputBotInlineMessageMediaAuto attributes, type and example
---
## Constructor: inputBotInlineMessageMediaAuto  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|message|[string](../types/string.md) | Yes|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [InputBotInlineMessage](../types/InputBotInlineMessage.md)


### Example:

```
$inputBotInlineMessageMediaAuto = ['_' => 'inputBotInlineMessageMediaAuto', 'message' => 'string', 'entities' => [MessageEntity, MessageEntity], 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inputBotInlineMessageMediaAuto", "message": "string", "entities": [MessageEntity], "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
inputBotInlineMessageMediaAuto={_='inputBotInlineMessageMediaAuto', message='string', entities={MessageEntity}, reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


