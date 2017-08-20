---
title: inlineKeyboardButtonTypeCallback
description: A button which sends to the bot special callback query
---
## Constructor: inlineKeyboardButtonTypeCallback  
[Back to constructors index](index.md)



A button which sends to the bot special callback query

### Attributes:

| Name     |    Type       | Required | Description |
|----------|---------------|----------|-------------|
|data|[bytes](../types/bytes.md) | Yes|Data to be sent to the bot through a callack query|



### Type: [InlineKeyboardButtonType](../types/InlineKeyboardButtonType.md)


### Example:

```
$inlineKeyboardButtonTypeCallback = ['_' => 'inlineKeyboardButtonTypeCallback', 'data' => 'bytes'];
```  

[PWRTelegram](https://pwrtelegram.xyz) json-encoded version:

```
{"_": "inlineKeyboardButtonTypeCallback", "data": "bytes"}
```


Or, if you're into Lua:  


```
inlineKeyboardButtonTypeCallback={_='inlineKeyboardButtonTypeCallback', data='bytes'}

```


