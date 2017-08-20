---
title: botInlineMessageMediaContact
description: botInlineMessageMediaContact attributes, type and example
---
## Constructor: botInlineMessageMediaContact  
[Back to constructors index](index.md)



### Attributes:

| Name     |    Type       | Required |
|----------|---------------|----------|
|phone\_number|[string](../types/string.md) | Yes|
|first\_name|[string](../types/string.md) | Yes|
|last\_name|[string](../types/string.md) | Yes|
|reply\_markup|[ReplyMarkup](../types/ReplyMarkup.md) | Optional|



### Type: [BotInlineMessage](../types/BotInlineMessage.md)


### Example:

```
$botInlineMessageMediaContact = ['_' => 'botInlineMessageMediaContact', 'phone_number' => 'string', 'first_name' => 'string', 'last_name' => 'string', 'reply_markup' => ReplyMarkup];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "botInlineMessageMediaContact", "phone_number": "string", "first_name": "string", "last_name": "string", "reply_markup": ReplyMarkup}
```


Or, if you're into Lua:  


```
botInlineMessageMediaContact={_='botInlineMessageMediaContact', phone_number='string', first_name='string', last_name='string', reply_markup=ReplyMarkup}

```



## Usage of reply_markup

You can provide bot API reply_markup objects here.  


