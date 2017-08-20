---
title: botInlineMessageText
description: botInlineMessageText attributes, type and example
---
## Constructor: botInlineMessageText  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|no\_webpage|[Bool](../types/Bool.md) | Optional|
|message|[string](../types/string.md) | Yes|
|entities|Array of [MessageEntity](../types/MessageEntity.md) | Optional|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [BotInlineMessage](../types/BotInlineMessage.md)


### Example:

```
$botInlineMessageText = ['_' => 'botInlineMessageText', 'no_webpage' => Bool, 'message' => 'string', 'entities' => [MessageEntity], 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInlineMessageText", "no_webpage": Bool, "message": "string", "entities": [MessageEntity], "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
botInlineMessageText={_='botInlineMessageText', no_webpage=Bool, message='string', entities={MessageEntity}, reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


