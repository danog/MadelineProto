---
title: callbackQueryAnswer
description: Contains answer of the bot to the callback query
---
## Constructor: callbackQueryAnswer  
[Back to constructors index](index.md)



Contains answer of the bot to the callback query

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|text|[string](../types/string.md) | Yes|Text of the answer|
|show\_alert|[Bool](../types/Bool.md) | Yes|If true, an alert should be shown to the user instead of a toast|
|url|[string](../types/string.md) | Yes|URL to be open|



### Type: [CallbackQueryAnswer](../types/CallbackQueryAnswer.md)


### Example:

```
$callbackQueryAnswer = ['_' => 'callbackQueryAnswer', 'text' => 'string', 'show_alert' => Bool, 'url' => 'string'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "callbackQueryAnswer", "text": "string", "show_alert": Bool, "url": "string"}
```


Or, if you're into Lua:  


```
callbackQueryAnswer={_='callbackQueryAnswer', text='string', show_alert=Bool, url='string'}

```


